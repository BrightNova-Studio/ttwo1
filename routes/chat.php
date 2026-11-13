<?php

use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Chat\InfoController;
use App\Http\Controllers\Chat\MatchController;
use App\Http\Controllers\Chat\RoomController;
use Illuminate\Support\Facades\Route;

// Member side. Deliberately has no session, no cookies, no CSRF token: members
// authenticate with a bearer token held only in the page's memory.
Route::get('/', [ChatController::class, 'page']);

// Plain explainer pages: no scripts, no storage.
Route::get('/how-it-works', [InfoController::class, 'show'])->defaults('page', 'how');
Route::get('/temp-vs-long', [InfoController::class, 'show'])->defaults('page', 'modes');
Route::get('/privacy', [InfoController::class, 'show'])->defaults('page', 'privacy');
Route::get('/faq', [InfoController::class, 'show'])->defaults('page', 'faq');

Route::prefix('api')->group(function () {
    Route::post('enter', [ChatController::class, 'enter']);
    Route::post('rooms', [RoomController::class, 'create']);
    Route::middleware('throttle:match')->prefix('match')->group(function () {
        Route::post('join', [MatchController::class, 'join']);
        Route::post('poll', [MatchController::class, 'poll']);
        Route::post('cancel', [MatchController::class, 'cancel']);
    });

    Route::middleware(['member', 'throttle:member'])->group(function () {
        Route::get('poll', [ChatController::class, 'poll']);
        Route::post('key-check', [ChatController::class, 'keyCheck']);
        Route::post('leave', [ChatController::class, 'leave']);
        Route::post('messages', [ChatController::class, 'send']);
        Route::post('messages/seen', [ChatController::class, 'seen']);
        Route::post('messages/{id}/view', [ChatController::class, 'view'])->whereNumber('id');
        Route::post('messages/{id}/react', [ChatController::class, 'react'])->whereNumber('id');
        Route::delete('messages/{id}', [ChatController::class, 'unsend'])->whereNumber('id');
        Route::post('typing', [ChatController::class, 'typing']);
        Route::post('profile', [ChatController::class, 'nick']);
        Route::post('room/disappear', [ChatController::class, 'disappear']);
        Route::post('room/clear', [ChatController::class, 'clear']);
        Route::post('room/end', [ChatController::class, 'end']);
    });
});
