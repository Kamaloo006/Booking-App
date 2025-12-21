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
    //get owner's proeperties by his token
    Route::get('/owner/token', [PropertyController::class, 'getPropertiesByOwner']);

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
    //add property to favorite
    Route::post('/property/favorite/{property}', [PropertyController::class, 'addPropertyToFavorite']);
    //remove property from favorite
    Route::delete('/property/rem_favorite/{property}', [PropertyController::class, 'removeFromFavorite']);
    // show my favorites
    Route::get('/favorite', [PropertyController::class, 'getFavorites']);
    //toogle
    Route::post('/favorites/{property}/toggle', [PropertyController::class, 'toggleFavorite']);

    //--------------------------------------|| Bookings

    // store new booking
    Route::post('/booking/property/{property_id}', [BookingController::class, 'store']);
    // update booking
    Route::put('/booking/{booking_id}', [BookingController::class, 'update']);
    // delete booking
    Route::delete('/booking/{booking_id}', [BookingController::class, 'delete']);


    // get all bookings
    Route::get('/bookings', [BookingController::class, 'getAllBookings']);
    Route::get('/bookings/cancelled', [BookingController::class, 'getCancelledBookings']);
    Route::get('/bookings/old', [BookingController::class, 'getOldBookings']);
    Route::get('/bookings/current/future', [BookingController::class, 'getCurrentAndFutureBookings']);
    Route::get('/bookings/current', [BookingController::class, 'getCurrentBookings']);
    Route::get('/bookings/future', [BookingController::class, 'getFutureBookings']);

    //This Routes for rating the booking
    Route::post('/rating/{booking}', [BookingController::class, 'addRating']);
    Route::put('/updaterating/{booking}', [BookingController::class, 'updateRating']);

    // --------------------------------- || Filter Properties
    Route::get('properties', [PropertyController::class, 'filterProperties']);



    // -------------------------------- || Admin Functions
    Route::middleware('checkAdmin')->group(function () {
        Route::get('/admin/pendingUsers', [UserController::class, 'getAllPendingUsers']);
        Route::patch('/admin/users/{user_id}/approve', [UserController::class, 'approveUser']);
        Route::post('/admin/users/{user_id}/reject', [UserController::class, 'rejectUser']);
    });
});
