<?php

use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');

    Route::get('/pages', [PageController::class, 'index'])->name('pages.index');
    Route::get('/pages/{page:slug}', [PageController::class, 'show'])->name('pages.show');
    Route::get('/pages/{page:slug}/edit', [PageController::class, 'edit'])->name('pages.edit');
});
