<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Room extends Model
{
    protected $guarded = [];

    protected $casts = ['last_activity_at' => 'datetime', 'expires_at' => 'datetime'];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Run a change inside a transaction that holds the room row, so every change gets a
     * unique, increasing revision number. Pollers asking "anything after rev N?" never miss one.
     */
    public function mutate(callable $fn): mixed
    {
        return DB::transaction(function () use ($fn) {
            $current = (int) DB::table('rooms')->where('id', $this->id)->lockForUpdate()->value('rev');
            $rev = $current + 1;
            DB::table('rooms')->where('id', $this->id)->update(['rev' => $rev, 'last_activity_at' => now()]);
            $this->rev = $rev;

            return $fn($rev);
        });
    }
}
