<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ReviewMemberController;

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Review routes
    Route::apiResource('reviews', ReviewController::class);

    // Article routes
    Route::post('/reviews/{review}/articles', [ArticleController::class, 'store']);
    Route::get('/reviews/{review}/articles', [ArticleController::class, 'index']);
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);

    // Review member routes
    Route::post('/reviews/{review}/invite', [ReviewMemberController::class, 'invite']);
    Route::get('/reviews/{review}/members', [ReviewMemberController::class, 'index']);
    Route::post('/reviews/{review}/accept', [ReviewMemberController::class, 'accept']);
    Route::delete('/reviews/{review}/members/{member}', [ReviewMemberController::class, 'destroy']);
    Route::put('/reviews/{review}/members/{member}/role', [ReviewMemberController::class, 'updateRole']);
});
