<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Admin routes only
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
});

// Review routes
Route::middleware('auth')->group(function () {
    Route::get('/reviews', function () {
        return Inertia::render('Reviews/Index');
    })->name('reviews.index');

    Route::get('/reviews/{id}', function ($id) {
        return Inertia::render('Reviews/Show', ['id' => $id]);
    })->name('reviews.show');
});

require __DIR__.'/auth.php';
