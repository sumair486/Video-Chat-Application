<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware('auth')->group(function () {
    // Chat routes
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/conversation/{user}', [ChatController::class, 'conversation'])->name('chat.conversation');
    Route::get('/chat/unread-count', [ChatController::class, 'unreadCount'])->name('chat.unread-count');
    Route::post('/chat/mark-read/{user}', [ChatController::class, 'markAsRead'])->name('chat.mark-read');

    // Video call routes
    Route::post('/signal', [CallController::class, 'signal'])->name('call.signal');

    // User status update route (for online/offline status)
    Route::post('/user/update-status', function () {
        auth()->user()->updateLastSeen();
        return response()->json(['success' => true]);
    })->name('user.update-status');
});


require __DIR__.'/auth.php';
