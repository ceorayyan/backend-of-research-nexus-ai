<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Root route - redirect based on auth status
Route::get('/', function () {
    if (auth()->check()) {
        // If logged in, redirect to dashboard
        return redirect()->route('dashboard');
    }
    // If not logged in, redirect to login
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
    
    Route::resource('users', UserController::class);
    Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/remove-logo', [\App\Http\Controllers\Admin\SettingsController::class, 'removeLogo'])->name('settings.removeLogo');
});

require __DIR__.'/auth.php';
