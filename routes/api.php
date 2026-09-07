<?php

use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
    Route::get('/me', fn (Request $request) => response()->json(['data' => $request->user()]));
    Route::delete('/token', function (Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Acceso revocado.']);
    });
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}/tasks', [TaskController::class, 'index']);
    Route::post('/users/{user}/tasks', [TaskController::class, 'store']);
    Route::patch('/tasks/{task}/complete', [TaskController::class, 'complete']);
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
});
