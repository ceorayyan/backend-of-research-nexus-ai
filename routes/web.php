<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Root route - redirect to admin if logged in, otherwise to login
Route::get('/', function () {
    if (auth()->check()) {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::get('/welcome', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Dashboard route (requires auth)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Review routes
    Route::get('/reviews', function () {
        return Inertia::render('Reviews/Index');
    })->name('web.reviews.index');

    Route::get('/reviews/{id}', function ($id) {
        return Inertia::render('Reviews/Show', ['id' => $id]);
    })->name('web.reviews.show');
});

// Admin routes only
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        $users = \App\Models\User::all();
        return Inertia::render('Admin/Dashboard', ['users' => $users]);
    })->name('dashboard');
    
    // Bulk import routes MUST come before resource routes to avoid greedy matching
    Route::get('users/bulk-import', function () {
        return Inertia::render('Admin/Users/BulkImport');
    })->name('users.bulkImport');
    Route::post('users/bulk-import', [UserController::class, 'bulkImport']);
    
    // Resource routes (these are greedy and must come after specific routes)
    Route::resource('users', UserController::class);
    
    Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/remove-logo', [\App\Http\Controllers\Admin\SettingsController::class, 'removeLogo'])->name('settings.removeLogo');
});

require __DIR__.'/auth.php';
