<?php

use App\Http\Controllers\Operator\LoginController;
use App\Http\Controllers\Operator\OperatorController;
use Illuminate\Support\Facades\Route;

// Operator console. Members never touch these routes and never get a session cookie.
Route::prefix(config('chat.operator_path'))->name('operator.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'show'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->middleware('throttle:6,1');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/', [OperatorController::class, 'index'])->name('dashboard');
        Route::get('stats', [OperatorController::class, 'stats'])->name('stats');
        Route::post('rooms', [OperatorController::class, 'store'])->name('rooms.store');
        Route::post('rooms/{room}/reissue/{slot}', [OperatorController::class, 'reissue'])->name('rooms.reissue');
        Route::patch('rooms/{room}', [OperatorController::class, 'update'])->name('rooms.update');
        Route::post('rooms/{room}/kick/{who}', [OperatorController::class, 'kick'])->name('rooms.kick');
        Route::post('rooms/{room}/reset', [OperatorController::class, 'reset'])->name('rooms.reset');
        Route::post('operators', [OperatorController::class, 'addOperator'])->name('operators.add');
        Route::delete('operators/{user}', [OperatorController::class, 'removeOperator'])->name('operators.remove');
        Route::post('rooms/{room}/toggle', [OperatorController::class, 'toggle'])->name('rooms.toggle');
        Route::post('rooms/{room}/wipe', [OperatorController::class, 'wipe'])->name('rooms.wipe');
        Route::delete('rooms/{room}', [OperatorController::class, 'destroy'])->name('rooms.destroy');
        Route::get('activity', [OperatorController::class, 'logs'])->name('logs');
        Route::get('settings', [OperatorController::class, 'settings'])->name('settings');
        Route::post('settings/password', [OperatorController::class, 'password'])->name('password');
        Route::post('logout', [LoginController::class, 'logout'])->name('logout');
    });
});
