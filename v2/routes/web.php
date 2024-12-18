<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IDCardController;

// Set login as the default page
Route::get('/', function () {
    return redirect('/login');
});

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/standard-login', [AuthController::class, 'standardLogin'])->name('standard.login');
Route::post('/ldap/login', [AuthController::class, 'ldapLogin'])->name('ldap.login');
Route::get('/saml/login', [AuthController::class, 'samlLogin'])->name('saml.login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/home', [IDCardController::class, 'index'])->name('home');
    Route::post('/generate', [IDCardController::class, 'generate'])->name('generate');
    Route::post('/generate-qr', [IDCardController::class, 'generateQR'])->name('generate.qr');
    Route::get('/gallery', [IDCardController::class, 'gallery'])->name('gallery');
    Route::delete('/cards/{id}', [IDCardController::class, 'destroy'])->name('cards.destroy');
});
