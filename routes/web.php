<?php

use Illuminate\Support\Facades\Route;




use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;



Route::get('/', function () {
    return view('welcome');
});
