<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IDCardController;
use App\Http\Controllers\AdminController;

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
    Route::get('/gallery', [IDCardController::class, 'gallery'])->name('gallery');
    
    // Non-staff routes
    Route::middleware(['not.staff'])->group(function () {
        Route::get('/home', [IDCardController::class, 'index'])->name('home');
        Route::post('/generate', [IDCardController::class, 'generate'])->name('generate');
        Route::post('/generate-qr', [IDCardController::class, 'generateQR'])->name('generate.qr');
        Route::delete('/cards/{id}', [IDCardController::class, 'destroy'])->name('cards.destroy');
    });

    Route::middleware(['admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::get('/users/create', [AdminController::class, 'create'])->name('admin.users.create');
        Route::post('/users', [AdminController::class, 'store'])->name('admin.users.store');
        Route::get('/users/{id}/edit', [AdminController::class, 'edit'])->name('admin.users.edit');
        Route::put('/users/{id}', [AdminController::class, 'update'])->name('admin.users.update');
        Route::delete('/users/{id}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    });
});




