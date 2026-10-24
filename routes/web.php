<?php

use App\Http\Controllers\DatingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->view('app')->header('Cache-Control', 'no-store, private'))->name('login');
Route::get('/cabinet', fn () => response()->view('app')->header('Cache-Control', 'no-store, private'))->middleware('auth')->name('cabinet');
Route::prefix('api')->group(function () {
    Route::get('/state', [DatingController::class, 'state']);
    Route::post('/login', [DatingController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [DatingController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/demo', [DatingController::class, 'demo'])->middleware('throttle:10,1');
    Route::middleware(['auth', 'throttle:120,1'])->group(function () {
        Route::post('/logout', [DatingController::class, 'logout']);
        Route::put('/profile', [DatingController::class, 'update']);
        Route::post('/photos', [DatingController::class, 'photo']);
        Route::delete('/photos', [DatingController::class, 'deletePhoto']);
        Route::delete('/account', [DatingController::class, 'destroy']);
        Route::post('/reactions/{target}', [DatingController::class, 'react']);
        Route::post('/undo', [DatingController::class, 'undo']);
        Route::get('/chats/{id}', [DatingController::class, 'messages']);
        Route::post('/chats/{id}', [DatingController::class, 'send']);
        Route::post('/block/{target}', [DatingController::class, 'block']);
        Route::post('/report/{target}', [DatingController::class, 'report']);
    });
});
