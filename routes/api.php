<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ProjectStatsController;
use App\Http\Controllers\Api\TaskCommentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('projects', ProjectController::class);

    Route::apiResource('projects.tasks', TaskController::class)
        ->scoped(['task' => 'id',]);

    Route::get('/projects/{project}/stats', ProjectStatsController::class);

    Route::get('/projects/{project}/tasks/{task}/comments',
        [TaskCommentController::class, 'index']);

    Route::post('/projects/{project}/tasks/{task}/comments',
        [TaskCommentController::class, 'store']);
});
