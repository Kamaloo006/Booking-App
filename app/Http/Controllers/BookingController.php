<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request , Property $property){
       $validatedata=$request->validated();
       if($property->user_id===$request->user()->id){
        return response()->json([
            'message'=>'you already own this property,you cannot book your own property'
        ],422);
       }
       $request_start=Carbon::parse($validatedata['start_date']);
       $request_end=Carbon::parse($validatedata['end_date']);
    //   
    $propertyStatus = $property->bookings()
    ->where(function($q1) use ($request_start, $request_end) {
        $q1->whereBetween('start_date', [$request_start, $request_end])
              ->orWhereBetween('end_date', [$request_start, $request_end])
              ->orWhere(function($q2) use ($request_start, $request_end) {
                  $q2->where('start_date', '<', $request_start)
                    ->where('end_date', '>', $request_end);
              });
    })->exists();
    if($propertyStatus){
        return response()->json([
            'message'=>'This property is already booked for the selected dates'
        ]);
    }
    
       $total_price=0;
   
       $date=$request_start->diffInDays($request_end)+1;
       $total_price=$property->price_per_day*$date;
       $validatedata['price']=$total_price;
       $validatedata['user_id']=$request->user()->id;
       $validatedata['property_id']=$property->id;
       $booking=Booking::create($validatedata);
       return response()->json([
        'message'=>'Operation Completed Successfully',
        'booking'=>$booking
       ],201);
    }
}
       
       

    

