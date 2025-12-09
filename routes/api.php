<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PropertyController;
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
Route::get('/properties', [PropertyController::class, 'showProperties']);

Route::get('/Users', [UserController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    //--------------------------------------|| Users 

    // get user by his token
    Route::get('/user/token', [UserController::class, 'getUserFromToken']);


    //--------------------------------------|| Properties
    // create new property
    Route::post('/property', [PropertyController::class, 'store'])->middleware('ownerOnly');

    //  show property by id
    Route::get("/property/{property_id}", [PropertyController::class, 'showProperty']);
    // Update property informations
    Route::put('/properties/{id}', [PropertyController::class, 'updateInfo'])->middleware('ownerOnly');

    // add new image to property
    Route::post('/properties/{id}/images', [PropertyController::class, 'addImages'])->middleware('ownerOnly');

    // replace image in property
    Route::post('/properties/{id}/images/{image_id}/replace', [PropertyController::class, 'replaceImage'])->middleware('ownerOnly');

    //  delete image in property
    Route::delete('/properties/{id}/images/{image_id}', [PropertyController::class, 'deleteImage'])->middleware('ownerOnly');

    // set main image in property
    Route::put('/properties/{id}/images/{image_id}/set-main', [PropertyController::class, 'setMainImage'])->middleware('ownerOnly');

    // delete property and its images
    Route::delete('/property/{id}', [PropertyController::class, 'destroy'])->middleware('ownerOnly');

    // add new features to property or update them
    Route::post('/properties/{property_id}/features', [PropertyController::class, 'storeFeatures'])->middleware('ownerOnly');

    //--------------------------------------|| Bookings
    // store new booking
    Route::post('/booking/property/{property_id}', [BookingController::class, 'store']);
    Route::put('/booking/{booking_id}', [BookingController::class, 'update']);
    Route::delete('/booking/{booking_id}', [BookingController::class, 'delete']);
    Route::get('/bookings', [BookingController::class, 'getAllBookings']);



    // --------------------------------- || Filter Properties
    Route::get('properties', [PropertyController::class, 'filterProperties']);
});
