<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $guarded = [];

    protected $casts = [
        'view_once' => 'boolean',
        'viewed_at' => 'datetime',
        'unsent_at' => 'datetime',
        'expires_at' => 'datetime',
        'delivered_at' => 'datetime',
        'seen_at' => 'datetime',
    ];
}
