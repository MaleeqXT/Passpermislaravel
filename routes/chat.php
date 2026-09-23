<?php

use App\Http\Controllers\Chat\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('chat/attachments/{attachment}', [ChatController::class, 'attachment']);
Route::get('chat/messages/{message}/attachment', [ChatController::class, 'legacyAttachment']);
Route::get('chat/avatars/{user}', [ChatController::class, 'avatar']);

Route::middleware(['auth:sanctum', \App\Http\Middleware\ResolveMonitorChat::class, 'role:student|monitor|admin|super-admin', 'throttle:chat'])
    ->controller(ChatController::class)->group(function () {
        Route::get('chat/session', 'session');
        Route::get('chat/unread', 'unread');
        Route::get('chat/contacts', 'contacts');
        Route::get('conversations', 'index');
        Route::post('conversations', 'store')->middleware('throttle:chat-write');
        Route::get('conversations/{conversation}/messages', 'messages');
        Route::post('conversations/{conversation}/messages', 'send')->middleware('throttle:chat-write');
        Route::post('conversations/{conversation}/read', 'read');
        Route::post('conversations/{conversation}/clear', 'clear')->middleware('throttle:chat-write');
        Route::delete('conversations/{conversation}', 'destroy')->middleware('throttle:chat-write');
        Route::delete('conversations/{conversation}/messages/{message}', 'destroyMessage')->middleware('throttle:chat-write');
    });
