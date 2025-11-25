<?php

use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/signUp', [UserController::class, 'register']);
Route::post('/signIn', [UserController::class, 'login']);
Route::post('/signOut', [UserController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/Users', [UserController::class, 'show']);

Route::middleware('auth:sanctum')->get('/user/token', [UserController::class, 'getUserFromToken']);
