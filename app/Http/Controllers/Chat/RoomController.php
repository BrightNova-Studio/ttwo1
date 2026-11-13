<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomEvent;
use App\Support\Ids;
use App\Support\RoomLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

/**
 * Self-service: anybody can open a room without an account or the operator. The three codes are
 * returned once, here, and stored only as keyed hashes, so nobody (us included) can read them back.
 */
class RoomController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate(['mode' => ['required', Rule::in(['temp', 'long'])]]);

        RoomLifecycle::sweepSoon();

        $hourKey = 'create-h:'.$request->ip();
        $dayKey = 'create-d:'.$request->ip();
        if (RateLimiter::tooManyAttempts($hourKey, (int) config('chat.create_per_hour'))
            || RateLimiter::tooManyAttempts($dayKey, (int) config('chat.create_per_day'))) {
            return response()->json([
                'error' => 'too_many_rooms',
                'message' => 'You have created a lot of rooms recently. Please wait a while and try again.',
            ], 429);
        }

        if (Room::count() >= (int) config('chat.max_rooms')) {
            return response()->json([
                'error' => 'busy',
                'message' => 'We are very busy right now. Please try again in a few minutes.',
            ], 503);
        }

        RateLimiter::hit($hourKey, 3600);
        RateLimiter::hit($dayKey, 86400);

        $key = Ids::freshChat();
        [$m1, $m2] = Ids::memberPair();

        DB::transaction(function () use ($data, $key, $m1, $m2) {
            $room = Room::create([
                'chat_hash' => Ids::hash($key),
                'mode' => $data['mode'],
                'source' => 'self',
                'last_active_at' => now(),
            ]);
            $room->members()->create(['slot' => 1, 'id_hash' => Ids::hash($m1)]);
            $room->members()->create(['slot' => 2, 'id_hash' => Ids::hash($m2)]);
            RoomEvent::record('created', $data['mode'], 'self');
        });

        return response()->json([
            'key' => $key,
            'you' => $m1,
            'partner' => $m2,
            'mode' => $data['mode'],
            'seconds' => RoomLifecycle::seconds($data['mode']),
        ], 201);
    }
}
