<?php

namespace App\Support;

use App\Models\Room;
use App\Models\RoomEvent;
use Illuminate\Support\Facades\Cache;

/**
 * A room lives while somebody uses it. Once both members have been gone for the mode's
 * time limit (Temp 30 minutes, Long 30 days) the room is deleted for good, with everything in it.
 */
class RoomLifecycle
{
    public static function seconds(string $mode): int
    {
        return (int) config('chat.modes.'.($mode === 'temp' ? 'temp' : 'long').'.seconds');
    }

    /** Is this room past its inactivity limit? */
    public static function lapsed(Room $room): bool
    {
        $last = $room->last_active_at ?? $room->created_at;

        return $last->lt(now()->subSeconds(self::seconds($room->mode)));
    }

    /** Delete every lapsed room (messages, members and all) and count each as "expired". */
    public static function sweep(): int
    {
        $count = 0;
        foreach (['temp', 'long'] as $mode) {
            $cut = now()->subSeconds(self::seconds($mode));
            $rooms = Room::where('mode', $mode)->where(function ($q) use ($cut) {
                $q->where('last_active_at', '<', $cut)
                    ->orWhere(fn ($q) => $q->whereNull('last_active_at')->where('created_at', '<', $cut));
            })->get(['id', 'mode', 'source']);

            foreach ($rooms as $room) {
                RoomEvent::record('expired', $room->mode, $room->source);
                $room->delete();
                $count++;
            }
        }

        // Rooms a member ended, once the partner has had time to notice.
        foreach (Room::where('status', 'closed')->where('updated_at', '<', now()->subMinutes(2))->get(['id']) as $room) {
            $room->delete();
        }

        // Random-connect queue: closed tabs and already-delivered matches are erased.
        \App\Models\MatchTicket::where('status', 'waiting')->where('last_poll_at', '<', now()->subSeconds(40))->delete();
        \App\Models\MatchTicket::where('status', 'matched')->where('updated_at', '<', now()->subMinutes(3))->delete();

        return $count;
    }

    /** Cheap to call on every public request: runs at most once every 20 seconds. */
    public static function sweepSoon(): void
    {
        if (Cache::add('room-sweep', 1, 20)) {
            self::sweep();
        }
    }

    /** Delete one room right now because it lapsed (used when someone knocks on it). */
    public static function expire(Room $room): void
    {
        RoomEvent::record('expired', $room->mode, $room->source);
        $room->delete();
    }
}
