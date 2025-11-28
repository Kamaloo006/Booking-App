<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BookingController extends Controller
{
    public function store(StoreBookingRequest $request,  $property_id)
    {
        try {

            $validatedData = $request->validated();


            $property = Property::findOrFail($property_id);


            if ($property->user_id === $request->user()->id) {
                return response()->json([
                    'message' => 'You already own this property, you cannot book your own property'
                ], 422);
            }


            $request_start = Carbon::parse($validatedData['start_date']);
            $request_end   = Carbon::parse($validatedData['end_date']);


            $propertyStatus = $property->bookings()
                ->where(function ($q1) use ($request_start, $request_end) {
                    $q1->whereBetween('start_date', [$request_start, $request_end])
                        ->orWhereBetween('end_date', [$request_start, $request_end])
                        ->orWhere(function ($q2) use ($request_start, $request_end) {
                            $q2->where('start_date', '<', $request_start)
                                ->where('end_date', '>', $request_end);
                        });
                })->exists();

            if ($propertyStatus) {
                return response()->json([
                    'message' => 'This property is already booked for the selected dates'
                ], 422);
            }


            $days = $request_start->diffInDays($request_end) + 1;
            $total_price = $property->price_per_day * $days;


            $validatedData['price']       = $total_price;
            $validatedData['user_id']     = $request->user()->id;
            $validatedData['property_id'] = $property->id;


            $booking = Booking::create($validatedData);

            return response()->json([
                'message' => 'Operation Completed Successfully',
                'booking' => $booking
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Property not found', 'message' => $e->getMessage()], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'error happened while booking', 'message' => $e->getMessage()], 40);
        }
    }
}
