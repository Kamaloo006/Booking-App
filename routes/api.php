<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/sign-up', [UserController::class, 'register']);
Route::post('/sign-in', [UserController::class, 'login']);
Route::post('/sign-out', [UserController::class, 'logout'])->middleware('auth:sanctum');
