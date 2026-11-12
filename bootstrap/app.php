<?php

use App\Http\Middleware\AuthenticateMember;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Member side: its own middleware group with NO session / cookie / CSRF.
            Route::middleware('chat')->group(base_path('routes/chat.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('chat', [SecurityHeaders::class]);
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->alias(['member' => AuthenticateMember::class]);
        $middleware->redirectGuestsTo(fn () => route('operator.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
