<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Anonymous counters (created / expired / removed) so statistics survive room deletion. */
class RoomEvent extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['created_at' => 'datetime'];

    public static function record(string $event, string $mode, string $source = 'self'): void
    {
        static::create(['event' => $event, 'mode' => $mode, 'source' => $source, 'created_at' => now()]);
    }
}
