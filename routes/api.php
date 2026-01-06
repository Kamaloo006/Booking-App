<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\UserController;
use App\Models\User;

use Illuminate\Support\Facades\Route;
  use Illuminate\Http\Request;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/signUp', [UserController::class, 'register']);
Route::post('/signIn', [UserController::class, 'login']);
Route::post('/signOut', [UserController::class, 'logout'])->middleware('auth:sanctum');
//Route::get('/properties', [PropertyController::class, 'showProperties']);

Route::get('/Users', [UserController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    //--------------------------------------|| Users 

    // get user by his token
    Route::get('/user/token', [UserController::class, 'getUserFromToken']);
    //get owner's proeperties by his token
    Route::get('/owner/token', [PropertyController::class, 'getPropertiesByOwner']);

    //--------------------------------------|| Properties
    // create new property
    Route::post('/property', [PropertyController::class, 'store']);

    //get property
    Route::get('/show/property/{property}', [PropertyController::class, 'getProperty']);

    //  show property by id
    Route::get("/property/{property_id}", [PropertyController::class, 'showProperty']);



    // ---------------------------------------- // Ratings

    Route::post('/rating/{property}', [PropertyController::class, 'addRating']);
   // Route::put('/updaterating/{property}', [PropertyController::class, 'updateRating']);


   

    // show my favorites
    Route::get('/favorites', [PropertyController::class, 'getFavorites']);
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




    // --------------------------------- || Filter Properties
    Route::get('properties', [PropertyController::class, 'filterProperties']);


    // -------------------------------- || Owner functions


    Route::middleware('ownerOnly')->group(function () {


        // Update property informations
        Route::put('/properties/{id}', [PropertyController::class, 'updateInfo']);

        // add new image to property
        Route::post('/properties/{id}/images', [PropertyController::class, 'addImages']);

        // replace image in property
        Route::post('/properties/{id}/images/{image_id}/replace', [PropertyController::class, 'replaceImage']);

        //  delete image in property
        Route::delete('/properties/{id}/images/{image_id}', [PropertyController::class, 'deleteImage']);

        // set main image in property
        Route::put('/properties/{id}/images/{image_id}/set-main', [PropertyController::class, 'setMainImage']);

        // delete property and its images
        Route::delete('/property/{id}', [PropertyController::class, 'destroy']);



        Route::get('owner/properties/pendingBookings', [BookingController::class, 'getAllPendingBookings']);
        Route::get('owner/properties/acceptedBookings', [BookingController::class, 'getAcceptedBookings']);
        Route::get('owner/properties/rejectedBookings', [BookingController::class, 'getRejectedBookings']);
        Route::post('owner/properties/bookings/{booking_id}/accept', [BookingController::class, 'acceptBooking']);
        Route::post('owner/properties/bookings/{booking_id}/reject', [BookingController::class, 'rejectBooking']);


        // ---------------------------------- || Owner Update Functions

        Route::get('owner/properties/pendingEditBookings', [BookingController::class, 'getPendingEditBookings']);
        Route::post('owner/properties/bookings/{booking_id}/accept_edit', [BookingController::class, 'acceptEdit']);
        Route::post('owner/properties/bookings/{booking_id}/reject_edit', [BookingController::class, 'rejectEdit']);


        Route::get('/owner/properties/currentBookings', [BookingController::class, 'getOwnerCurrentBookings']);
    });


    Route::get('user/bookings/pending', [BookingController::class, 'getMyPendingBookings']);
    Route::get('user/bookings/pending_edit', [BookingController::class, 'getMyPendingEditBookings']);

    Route::get('/test-firebase', [UserController::class, 'testFirebaseConnection']);

    // -------------------------------- || Admin Functions
    Route::middleware('checkAdmin')->group(function () {
        Route::get('/admin/pendingUsers', [UserController::class, 'getAllPendingUsers']);
        Route::patch('/admin/users/{user_id}/approve', [UserController::class, 'approveUser']);
        Route::post('/admin/users/{user_id}/reject', [UserController::class, 'rejectUser']);
        Route::delete('/admin/{user_id}/delete', [UserController::class, 'deleteUser']);
    });

  

    
});

Route::post('/send-notification', function (Request $request) {
    $request->validate([
        'fcm_token' => 'required|string',
        'title' => 'required|string',
        'body' => 'required|string',
    ]);

    $messaging = app('firebase.messaging');

    // Create notification
    $notification = Notification::create($request->title, $request->body);

    // Attach the FCM token (this fixes "missing target")
    $message = CloudMessage::withTarget('token', $request->fcm_token)
                           ->withNotification($notification);

    try {
        $messaging->send($message);

        return response()->json([
            'success' => true,
            'message' => 'Notification sent successfully!'
        ]);

    } catch (\Kreait\Firebase\Exception\MessagingException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Firebase Messaging Error: ' . $e->getMessage()
        ], 500);

    } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Firebase General Error: ' . $e->getMessage()
        ], 500);
    }
});