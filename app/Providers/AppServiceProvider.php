<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Per signed-in member: generous for the 1-2 second polling, tight enough to stop abuse.
        RateLimiter::for('member', function (Request $request) {
            $token = $request->bearerToken();

            return Limit::perMinute(400)->by($token ? 't:'.sha1($token) : 'ip:'.$request->ip());
        });
    }
}
