<?php

namespace App\Providers;

use App\Support\Client;
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
        RateLimiter::for('match', fn (Request $request) => Limit::perMinute(150)->by('ip:'.Client::key($request->ip())));
        RateLimiter::for('member', function (Request $request) {
            $token = $request->bearerToken();

            return Limit::perMinute(400)->by($token ? 't:'.sha1($token) : 'ip:'.Client::key($request->ip()));
        });
        // Entry points that make rooms or hand out puzzles: generous for people, tight for scripts.
        RateLimiter::for('entry', fn (Request $request) => Limit::perMinute(40)->by('entry:'.Client::key($request->ip())));
    }
}
