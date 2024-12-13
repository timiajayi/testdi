<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IDCardController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [IDCardController::class, 'index'])->name('home');
    Route::get('/home', [IDCardController::class, 'index']);
    Route::post('/generate-qr', [IDCardController::class, 'generateQR'])->name('generate.qr');
    Route::post('/generate', [IDCardController::class, 'generate'])->name('generate');
    Route::get('/gallery', [IDCardController::class, 'gallery'])->name('gallery');
    Route::delete('/cards/{id}', [IDCardController::class, 'destroy'])->name('cards.destroy');
});
