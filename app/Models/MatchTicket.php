<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchTicket extends Model
{
    protected $guarded = [];

    protected $hidden = ['token_hash'];

    protected $casts = ['last_poll_at' => 'datetime'];
}
