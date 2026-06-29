<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ComentarioController;
use App\Http\Controllers\Api\LikeController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function () { return response()->json(auth('api')->user()); });
	
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::post('/posts', [PostController::class, 'store']);       
    Route::put('/posts/{id}', [PostController::class, 'update']);   
    Route::delete('/posts/{id}', [PostController::class, 'destroy']); 
	
    Route::get('/posts/{id}/comentarios', [ComentarioController::class, 'index']);
    Route::post('/posts/{id}/comentarios', [ComentarioController::class, 'store']);

    Route::post('/posts/{id}/like', [LikeController::class, 'toggle']);
	
	Route::get('/posts/{id}/summary', [PostController::class, 'summary']);

});
