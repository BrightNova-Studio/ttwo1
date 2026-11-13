<?php

namespace App\Http\Middleware;

use App\Models\Member;
use App\Models\Room;
use App\Support\Ids;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Members authenticate with a random bearer token that lives only in the page's memory.
 * No cookie, no localStorage: close the tab and the token is gone.
 */
class AuthenticateMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            return $this->deny('signed_out', 'Please sign in.');
        }

        $member = Member::where('token_hash', Ids::tokenHash($token))->first();
        if (! $member) {
            return $this->deny('session_ended', 'This session has ended. It may have been opened on another device.');
        }

        $room = $member->room;
        if (! $room || ! $room->isActive()) {
            $member->update(['token_hash' => null]);

            return $this->deny('room_unavailable', 'This room is not available right now.', 403);
        }

        $idle = (int) config('chat.session_idle_seconds');
        if ($member->last_seen_at && $member->last_seen_at->lt(now()->subSeconds($idle))) {
            $member->update(['token_hash' => null]);

            return $this->deny('session_expired', 'Your session timed out. Please sign in again.');
        }

        Member::whereKey($member->id)->update(['last_seen_at' => now()]);
        // Presence keeps the room alive: any signed-in member resets the expiry clock.
        if (! $room->last_active_at || $room->last_active_at->lt(now()->subSeconds(15))) {
            Room::whereKey($room->id)->update(['last_active_at' => now()]);
        }

        $request->attributes->set('member', $member);
        $request->attributes->set('room', $room);

        return $next($request);
    }

    private function deny(string $code, string $message, int $status = 401): Response
    {
        return response()->json(['error' => $code, 'message' => $message], $status);
    }
}
