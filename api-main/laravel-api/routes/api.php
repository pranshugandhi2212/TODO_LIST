<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WorkspacePreferenceController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('product', ProductController::class);

// Public auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::prefix('auth')->group(function (): void {
    Route::get('/google/config', [AuthController::class, 'googleConfig']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/google', [AuthController::class, 'googleLogin']);
});

// Protected routes
Route::middleware('auth:sanctum')->apiResource('product', ProductController::class);

Route::get('/feedbacks', [FeedbackController::class, 'index']);
Route::post('/feedbacks', [FeedbackController::class, 'store']);
Route::get('/comments', [CommentController::class, 'index']);
Route::post('/comments', [CommentController::class, 'store']);
Route::post('/contact', [ContactController::class, 'store']);
Route::post('/reset-password/request-otp', [ResetPasswordController::class, 'sendOtp']);
Route::post('/reset-password', [ResetPasswordController::class, 'reset']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/workspace/preferences', [WorkspacePreferenceController::class, 'show']);
    Route::put('/workspace/preferences', [WorkspacePreferenceController::class, 'update']);
    Route::post('/tasks/{task}/attachment', [TaskController::class, 'storeAttachments']);
    Route::post('/tasks/{task}/attachments', [TaskController::class, 'storeAttachments']);
    Route::post('/tasks/{task}/comment', [TaskController::class, 'storeComment']);
    Route::post('/tasks/{task}/comments', [TaskController::class, 'storeComment']);
    Route::apiResource('tasks', TaskController::class)->only([
        'index',
        'show',
        'store',
        'update',
        'destroy',
    ]);
});
