<?php

use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\AdminCoachController;
use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Chat member & Kelola Coach
|--------------------------------------------------------------------------
*/

// Member: chat dengan admin
Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::get('/chat/messages', [ChatController::class, 'poll'])->name('chat.poll');
    Route::post('/chat', [ChatController::class, 'send'])->name('chat.send');
});

// Admin: kotak masuk chat, broadcast, kelola coach
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::get('/admin/chat', [AdminChatController::class, 'index'])->name('admin.chat.index');
    Route::get('/admin/chat/{member}/messages', [AdminChatController::class, 'poll'])->name('admin.chat.poll');
    Route::post('/admin/chat/{member}', [AdminChatController::class, 'send'])->name('admin.chat.send');
    Route::post('/admin/chat-broadcast', [AdminChatController::class, 'broadcast'])->name('admin.chat.broadcast');

    Route::resource('/admin/coaches', AdminCoachController::class)
        ->names('admin.coaches')
        ->except(['show']);
});
