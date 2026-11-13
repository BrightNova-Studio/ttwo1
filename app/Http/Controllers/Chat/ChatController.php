<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Message;
use App\Models\Room;
use App\Support\Ids;
use App\Support\RoomLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * Everything here handles CIPHERTEXT only. Message text, nicknames, reactions and the room
 * "key check" are encrypted in the browser with a key the server never sees.
 */
class ChatController extends Controller
{
    public function page()
    {
        RoomLifecycle::sweepSoon();

        return view('chat', [
            'supportEmail' => config('chat.support_email'),
            'modes' => [
                'temp' => (int) config('chat.modes.temp.seconds'),
                'long' => (int) config('chat.modes.long.seconds'),
            ],
        ]);
    }

    /* ---------------------------------------------------------------- sign in */

    public function enter(Request $request): JsonResponse
    {
        $data = $request->validate([
            'chat_id' => ['required', 'string', 'max:64'],
            'member_id' => ['required', 'string', 'max:64'],
        ]);

        $ipKey = 'enter-ip:'.$request->ip();
        if (RateLimiter::tooManyAttempts($ipKey, 15)) {
            return $this->tooMany();
        }
        RateLimiter::hit($ipKey, 60);

        $chatHash = Ids::hash($data['chat_id']);
        $lockKey = 'chat-lock:'.$chatHash;
        $failKey = 'chat-fail:'.$chatHash;

        if (Cache::has($lockKey)) {
            return $this->tooMany();
        }

        $room = Room::where('chat_hash', $chatHash)->first();
        if ($room && RoomLifecycle::lapsed($room)) {
            RoomLifecycle::expire($room); // nobody was around for too long: it is gone for good
            $room = null;
        }
        RoomLifecycle::sweepSoon();
        $memberHash = Ids::hash($data['member_id']);
        $member = $room?->members->first(fn (Member $m) => hash_equals($m->id_hash, $memberHash));

        if (! $room || ! $member || ! $room->isActive()) {
            $fails = (int) Cache::get($failKey, 0) + 1;
            Cache::put($failKey, $fails, now()->addMinutes((int) config('chat.lockout_minutes')));
            if ($fails >= (int) config('chat.max_failures')) {
                Cache::put($lockKey, true, now()->addMinutes((int) config('chat.lockout_minutes')));
                Cache::forget($failKey);
            }
            usleep(random_int(150000, 350000)); // blunt timing guesses

            return response()->json([
                'error' => 'invalid',
                'message' => "Those details didn't match, or this room is unavailable. Check them, or contact support.",
            ], 401);
        }

        Cache::forget($failKey);

        $token = Ids::token();
        Room::whereKey($room->id)->update(['last_active_at' => now()]);
        $member->update([
            'token_hash' => Ids::tokenHash($token),
            'sessions' => $member->sessions + 1,
            'joined_at' => $member->joined_at ?? now(),
            'last_seen_at' => now(),
            'typing_until' => null,
        ]);

        return response()->json([
            'token' => $token,
            'slot' => $member->slot,
            'key_check' => $room->key_check,
            'nick_enc' => $member->nick_enc,
            'room' => $this->roomState($room->refresh()),
            'peer' => $this->peerState($room, $member->slot),
            'idle_seconds' => (int) config('chat.session_idle_seconds'),
        ]);
    }

    /** First member in sets the shared-secret check value; after that it is fixed. */
    public function keyCheck(Request $request): JsonResponse
    {
        $data = $request->validate(['key_check' => ['required', 'string', 'max:400']]);
        $room = $request->attributes->get('room');

        $set = Room::whereKey($room->id)->whereNull('key_check')->update(['key_check' => $data['key_check']]);

        // Somebody else set it a moment earlier: hand back the existing value so this client can verify against it.
        return response()->json(['ok' => (bool) $set, 'key_check' => $set ? $data['key_check'] : Room::whereKey($room->id)->value('key_check')], $set ? 200 : 409);
    }

    public function leave(Request $request): JsonResponse
    {
        $member = $request->attributes->get('member');
        Member::whereKey($member->id)->update([
            'token_hash' => null,
            'typing_until' => null,
            'last_seen_at' => now()->subMinute(),
        ]);

        return response()->json(['ok' => true]);
    }

    /* ---------------------------------------------------------------- polling */

    public function poll(Request $request): JsonResponse
    {
        /** @var Room $room */
        $room = $request->attributes->get('room');
        /** @var Member $me */
        $me = $request->attributes->get('member');
        $since = max(0, (int) $request->query('since', 0));
        $them = $me->slot === 1 ? 2 : 1;

        // Disappearing messages: expired rows are removed for good.
        Message::where('room_id', $room->id)->whereNotNull('expires_at')->where('expires_at', '<', now())->delete();

        // Anything the other person sent that we are now receiving counts as delivered.
        $undelivered = Message::where('room_id', $room->id)->where('sender_slot', $them)->whereNull('delivered_at')->exists();
        if ($undelivered) {
            $room->mutate(function ($rev) use ($room, $them) {
                Message::where('room_id', $room->id)->where('sender_slot', $them)->whereNull('delivered_at')
                    ->update(['delivered_at' => now(), 'rev' => $rev]);
            });
        }

        $roomRow = Room::findOrFail($room->id);
        $upTo = (int) $roomRow->rev;
        $limit = 300;

        $rows = Message::where('room_id', $room->id)->where('rev', '>', $since)->where('rev', '<=', $upTo)
            ->orderBy('rev')->limit($limit)->get();

        $cursor = $rows->count() >= $limit ? (int) $rows->max('rev') : max($since, $upTo);

        return response()->json([
            'rev' => $cursor,
            'now' => now()->timestamp,
            'room' => $this->roomState($roomRow),
            'peer' => $this->peerState($roomRow, $me->slot),
            'messages' => $rows->map(fn (Message $m) => $this->present($m, $me->slot))->values(),
        ]);
    }

    /* --------------------------------------------------------------- messages */

    public function send(Request $request): JsonResponse
    {
        /** @var Room $room */
        $room = $request->attributes->get('room');
        $me = $request->attributes->get('member');

        $data = $request->validate([
            'body_enc' => ['required', 'string', 'max:'.config('chat.max_body_chars')],
            'view_once' => ['sometimes', 'boolean'],
            'reply_to' => ['nullable', 'integer'],
        ]);

        $replyTo = null;
        if (! empty($data['reply_to'])) {
            $replyTo = Message::where('room_id', $room->id)->whereKey($data['reply_to'])->value('id');
        }

        $roomRow = Room::findOrFail($room->id);
        $msg = $room->mutate(fn ($rev) => Message::create([
            'room_id' => $room->id,
            'sender_slot' => $me->slot,
            'body_enc' => $data['body_enc'],
            'view_once' => (bool) ($data['view_once'] ?? false),
            'reply_to' => $replyTo,
            'rev' => $rev,
            'expires_at' => $roomRow->disappear_seconds > 0 ? now()->addSeconds($roomRow->disappear_seconds) : null,
        ]));

        Member::whereKey($me->id)->update(['typing_until' => null]);

        return response()->json(['message' => $this->present($msg->refresh(), $me->slot, true)], 201);
    }

    /** The receiver opens a view-once message: they get the ciphertext once, the server deletes it. */
    public function view(Request $request, int $id): JsonResponse
    {
        $room = $request->attributes->get('room');
        $me = $request->attributes->get('member');

        $body = $room->mutate(function ($rev) use ($room, $me, $id) {
            $m = Message::where('room_id', $room->id)->whereKey($id)->lockForUpdate()->first();
            if (! $m || ! $m->view_once || $m->sender_slot === $me->slot || $m->viewed_at || $m->unsent_at || ! $m->body_enc) {
                return null;
            }
            $body = $m->body_enc;
            $m->update(['body_enc' => null, 'viewed_at' => now(), 'rev' => $rev]);

            return $body;
        });

        if ($body === null) {
            return response()->json(['error' => 'gone', 'message' => 'This message can no longer be opened.'], 410);
        }

        return response()->json(['body_enc' => $body]);
    }

    public function unsend(Request $request, int $id): JsonResponse
    {
        $room = $request->attributes->get('room');
        $me = $request->attributes->get('member');

        $done = $room->mutate(function ($rev) use ($room, $me, $id) {
            $m = Message::where('room_id', $room->id)->where('sender_slot', $me->slot)->whereKey($id)->first();
            if (! $m || $m->unsent_at) {
                return false;
            }
            $m->update(['body_enc' => null, 'react_1' => null, 'react_2' => null, 'unsent_at' => now(), 'rev' => $rev]);

            return true;
        });

        return response()->json(['ok' => $done], $done ? 200 : 404);
    }

    public function react(Request $request, int $id): JsonResponse
    {
        $room = $request->attributes->get('room');
        $me = $request->attributes->get('member');
        $data = $request->validate(['emoji_enc' => ['nullable', 'string', 'max:400']]);

        $done = $room->mutate(function ($rev) use ($room, $me, $id, $data) {
            $m = Message::where('room_id', $room->id)->whereKey($id)->whereNull('unsent_at')->first();
            if (! $m) {
                return false;
            }
            $m->update(['react_'.$me->slot => $data['emoji_enc'] ?? null, 'rev' => $rev]);

            return true;
        });

        return response()->json(['ok' => $done], $done ? 200 : 404);
    }

    public function seen(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $me = $request->attributes->get('member');
        $data = $request->validate(['up_to' => ['required', 'integer', 'min:1']]);
        $them = $me->slot === 1 ? 2 : 1;

        $pending = Message::where('room_id', $room->id)->where('sender_slot', $them)
            ->where('id', '<=', $data['up_to'])->whereNull('seen_at')->exists();

        if ($pending) {
            $room->mutate(function ($rev) use ($room, $them, $data) {
                Message::where('room_id', $room->id)->where('sender_slot', $them)
                    ->where('id', '<=', $data['up_to'])->whereNull('seen_at')
                    ->update(['seen_at' => now(), 'delivered_at' => DB::raw('COALESCE(delivered_at, CURRENT_TIMESTAMP)'), 'rev' => $rev]);
            });
        }

        return response()->json(['ok' => true]);
    }

    public function typing(Request $request): JsonResponse
    {
        $me = $request->attributes->get('member');
        Member::whereKey($me->id)->update(['typing_until' => now()->addSeconds(4)]);

        return response()->json(['ok' => true]);
    }

    /* --------------------------------------------------------- room & profile */

    public function nick(Request $request): JsonResponse
    {
        $me = $request->attributes->get('member');
        $data = $request->validate(['nick_enc' => ['nullable', 'string', 'max:600']]);
        Member::whereKey($me->id)->update(['nick_enc' => $data['nick_enc'] ?? null]);

        return response()->json(['ok' => true]);
    }

    public function disappear(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');
        $data = $request->validate(['seconds' => ['required', 'integer', Rule::in(config('chat.disappear_options'))]]);

        $room->mutate(fn () => Room::whereKey($room->id)->update(['disappear_seconds' => $data['seconds']]));

        return response()->json(['ok' => true, 'seconds' => $data['seconds']]);
    }

    /** Either member can wipe the whole conversation for both of them. */
    public function clear(Request $request): JsonResponse
    {
        $room = $request->attributes->get('room');

        $epoch = $room->mutate(function () use ($room) {
            Message::where('room_id', $room->id)->delete();
            Room::whereKey($room->id)->increment('epoch');

            return (int) Room::whereKey($room->id)->value('epoch');
        });

        return response()->json(['ok' => true, 'epoch' => $epoch]);
    }

    /* ---------------------------------------------------------------- helpers */

    private function roomState(Room $room): array
    {
        return [
            'disappear' => (int) $room->disappear_seconds,
            'epoch' => (int) $room->epoch,
        ];
    }

    private function peerState(Room $room, int $mySlot): array
    {
        $peer = Member::where('room_id', $room->id)->where('slot', $mySlot === 1 ? 2 : 1)->first();

        return [
            'joined' => (bool) $peer?->joined_at,
            'online' => (bool) $peer?->isOnline(),
            'typing' => (bool) ($peer?->isOnline() && $peer->typing_until && $peer->typing_until->isFuture()),
            'nick_enc' => $peer?->nick_enc,
            'sessions' => (int) ($peer?->sessions ?? 0),
        ];
    }

    private function present(Message $m, int $mySlot, bool $justSent = false): array
    {
        $mine = $m->sender_slot === $mySlot;
        $them = $mySlot === 1 ? 2 : 1;

        return [
            'id' => $m->id,
            'mine' => $mine,
            // View-once bodies never travel in polls; the receiver fetches them once via /view.
            'body_enc' => ($m->view_once || $m->unsent_at) ? null : $m->body_enc,
            'view_once' => $m->view_once,
            'viewed' => (bool) $m->viewed_at,
            'unsent' => (bool) $m->unsent_at,
            'reply_to' => $m->reply_to,
            'my_reaction' => $m->{'react_'.$mySlot},
            'their_reaction' => $m->{'react_'.$them},
            'expires_at' => $m->expires_at?->timestamp,
            'delivered' => (bool) $m->delivered_at,
            'seen' => (bool) $m->seen_at,
            'created_at' => $m->created_at->timestamp,
        ];
    }

    private function tooMany(): JsonResponse
    {
        return response()->json([
            'error' => 'locked',
            'message' => 'Too many attempts. Please wait a few minutes before trying again.',
        ], 429);
    }
}
