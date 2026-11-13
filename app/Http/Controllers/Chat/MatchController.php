<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\MatchTicket;
use App\Models\Room;
use App\Models\RoomEvent;
use App\Support\Challenge;
use App\Support\Client;
use App\Support\Ids;
use App\Support\RoomLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * Random connect. Two people who are looking at the same time are paired in order of arrival
 * (same topic first, "Anyone" matches everybody) and dropped into a fresh Temp room.
 *
 * The browsers agree on the secret phrase themselves (ECDH key exchange over the public keys
 * relayed here), so the server never learns the key that encrypts the chat.
 */
class MatchController extends Controller
{
    private const FRESH = 15;      // a waiting ticket not polled for this many seconds is a closed tab
    private const GIVE_UP = 300;   // stop looking after 5 minutes

    public function join(Request $request): JsonResponse
    {
        $data = $request->validate([
            'topic' => ['required', Rule::in(array_merge(['any'], array_keys(config('chat.random_topics'))))],
            'pub' => ['required', 'string', 'max:200', 'regex:/^[A-Za-z0-9_-]+$/'],
            'adult' => ['accepted'],
        ]);

        $client = Client::key($request->ip());
        if ($refused = Challenge::verify($request, 'match', $client)) {
            return $refused;
        }

        $key = 'match-join:'.$client;
        if (RateLimiter::tooManyAttempts($key, (int) config('chat.random_joins_per_hour'))) {
            return response()->json(['error' => 'slow_down', 'message' => 'You have searched a lot recently. Please take a short break.'], 429);
        }
        if (Room::count() >= (int) config('chat.max_rooms') || MatchTicket::where('status', 'waiting')->count() > 5000) {
            return response()->json(['error' => 'busy', 'message' => 'We are very busy right now. Please try again in a few minutes.'], 503);
        }
        RateLimiter::hit($key, 3600);

        RoomLifecycle::sweepSoon();

        $token = Ids::token();
        $ticket = MatchTicket::create([
            'token_hash' => Ids::tokenHash($token),
            'topic' => $data['topic'],
            'pub' => $data['pub'],
            'last_poll_at' => now(),
        ]);

        $this->tryMatch($ticket);

        return response()->json(['ticket' => $token] + $this->state($ticket->refresh()), 201);
    }

    public function poll(Request $request): JsonResponse
    {
        $ticket = $this->find($request);
        if (! $ticket) {
            return response()->json(['status' => 'gone'], 404);
        }

        if ($ticket->status === 'waiting') {
            if ($ticket->created_at->lt(now()->subSeconds(self::GIVE_UP))) {
                $ticket->delete();

                return response()->json(['status' => 'timeout']);
            }
            $ticket->update(['last_poll_at' => now()]);
            $this->tryMatch($ticket); // somebody may have arrived and be waiting for us
            $ticket->refresh();
        }

        return response()->json($this->state($ticket));
    }

    public function cancel(Request $request): JsonResponse
    {
        $this->find($request)?->delete();

        return response()->json(['ok' => true]);
    }

    private function find(Request $request): ?MatchTicket
    {
        $token = (string) $request->input('ticket', '');

        return $token === '' ? null : MatchTicket::where('token_hash', Ids::tokenHash($token))->first();
    }

    /** Pair this ticket with the best waiting one, if any: same topic first, then oldest. */
    private function tryMatch(MatchTicket $me): void
    {
        DB::transaction(function () use ($me) {
            $me = MatchTicket::whereKey($me->id)->lockForUpdate()->first();
            if (! $me || $me->status !== 'waiting') {
                return;
            }

            $q = MatchTicket::where('status', 'waiting')->where('id', '!=', $me->id)
                ->where('last_poll_at', '>', now()->subSeconds(self::FRESH));
            if ($me->topic !== 'any') {
                $q->where(fn ($w) => $w->where('topic', $me->topic)->orWhere('topic', 'any'));
            }
            $other = $q->orderByRaw('CASE WHEN topic = ? THEN 0 ELSE 1 END', [$me->topic])->orderBy('id')->lockForUpdate()->first();
            if (! $other) {
                return;
            }

            $key = Ids::freshChat();
            [$p1, $p2] = Ids::memberPair();
            $room = Room::create([
                'chat_hash' => Ids::hash($key),
                'mode' => 'temp',
                'source' => 'random',
                'last_active_at' => now(),
            ]);
            $room->members()->create(['slot' => 1, 'id_hash' => Ids::hash($p1)]);
            $room->members()->create(['slot' => 2, 'id_hash' => Ids::hash($p2)]);
            RoomEvent::record('created', 'temp', 'random');

            // The one who waited longest is member 1 and gets to set the room up first.
            $other->update(['status' => 'matched', 'room_key' => $key, 'pass_code' => $p1, 'peer_pub' => $me->pub]);
            $me->update(['status' => 'matched', 'room_key' => $key, 'pass_code' => $p2, 'peer_pub' => $other->pub]);
        });
    }

    private function state(MatchTicket $t): array
    {
        if ($t->status === 'matched') {
            return ['status' => 'matched', 'key' => $t->room_key, 'pass' => $t->pass_code, 'peer_pub' => $t->peer_pub];
        }

        return [
            'status' => 'waiting',
            'looking' => MatchTicket::where('status', 'waiting')->where('last_poll_at', '>', now()->subSeconds(self::FRESH))->count(),
        ];
    }
}
