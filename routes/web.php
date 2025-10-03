<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FaceAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Models\User;
use App\Services\UserStatusService;
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
    
    // NEW: File download and management routes
    Route::get('/chat/download/{message}', [ChatController::class, 'downloadFile'])->name('chat.download');
    Route::get('/chat/file-info/{message}', [ChatController::class, 'fileInfo'])->name('chat.file-info');
    Route::delete('/chat/message/{message}', [ChatController::class, 'deleteMessage'])->name('chat.delete');
    Route::get('/chat/allowed-file-types', [ChatController::class, 'getAllowedFileTypes'])->name('chat.file-types');
   // Type indicator
    Route::post('/chat/typing', [ChatController::class, 'typing'])->middleware('auth');
    // Video call routes
    Route::post('/signal', [CallController::class, 'signal'])->name('call.signal');
    Route::get('/call/history', [CallController::class, 'callHistory'])->name('call.history');
    Route::get('/call/check-availability/{user}', [CallController::class, 'checkAvailability'])->name('call.availability');
    
    // User status routes
    Route::post('/user/update-status', [UserController::class, 'updateStatus'])->name('user.update-status');



    // Face enrollment/management
    Route::get('/face-auth/enroll', [FaceAuthController::class, 'showEnrollment'])->name('face-auth.enroll');
    Route::post('/face-auth/enroll', [FaceAuthController::class, 'enrollFace'])->name('face-auth.enroll.submit');
    
    // Face verification (for sensitive operations)
    Route::post('/face-auth/verify', [FaceAuthController::class, 'verifyFace'])->name('face-auth.verify');
    
    // Face auth management
    Route::post('/face-auth/disable', [FaceAuthController::class, 'disableFaceAuth'])->name('face-auth.disable');
    Route::get('/face-auth/stats', [FaceAuthController::class, 'getFaceAuthStats'])->name('face-auth.stats');
    
    // Testing endpoint for development
    Route::post('/face-auth/test', [FaceAuthController::class, 'testFaceDetection'])->name('face-auth.test');
    
    // Like Reaction

     Route::post('/messages/{message}/react', [ChatController::class, 'addReaction']);
    Route::delete('/messages/{message}/react', [ChatController::class, 'removeReaction']);

    Route::get('/messages/{message}/reactions', [ChatController::class, 'getMessageReactions']);
});

Route::middleware('guest')->group(function () {
    // Face authentication login
    Route::get('/face-login', [FaceAuthController::class, 'showFaceLogin'])->name('face-auth.login');
    Route::post('/face-auth/authenticate', [FaceAuthController::class, 'authenticateWithFace'])->name('face-auth.authenticate');
    
    // Face authentication registration (optional)
    Route::get('/face-register', function () {
        return view('face-auth.register');
    })->name('face-auth.register');
    Route::post('/face-auth/register', [FaceAuthController::class, 'registerWithFace'])->name('face-auth.register.submit');
});

require __DIR__.'/auth.php';
