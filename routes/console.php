<?php

use App\Models\Message;
use Illuminate\Support\Facades\Schedule;

// Sweeps expired (disappearing) messages even in rooms nobody has open.
// Add a cron job:  * * * * * php /path/to/artisan schedule:run
Schedule::call(function () {
    Message::whereNotNull('expires_at')->where('expires_at', '<', now())->delete();
})->everyMinute()->name('chat-prune');

// Deletes rooms whose members have both been away too long (Temp 30 min, Long 30 days).
Schedule::call(fn () => \App\Support\RoomLifecycle::sweep())->everyMinute()->name('room-expiry');
