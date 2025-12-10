<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BookingController extends Controller
{
    use AuthorizesRequests;

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

            // if (!$property->is_available) {
            //     return response()->json([
            //         'message' => 'This property is not available for booking'
            //     ], 422);
            // }


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

            // if (!$propertyStatus) $property->update(['is_available' => true]);



            $days = $request_start->diffInDays($request_end) + 1;
            $total_price = $property->price_per_day * $days;


            $validatedData['price']       = $total_price;
            $validatedData['user_id']     = $request->user()->id;
            $validatedData['property_id'] = $property->id;


            $booking = Booking::create($validatedData);


            // $property->update(['is_available' => false]);


            return response()->json([
                'message' => 'Operation Completed Successfully',
                'booking' => $booking
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Property not found', 'message' => $e->getMessage()], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'error happened while booking', 'message' => $e->getMessage()], 500);
        }
    }



    public function update(UpdateBookingRequest $request, $booking_id)
    {
        try {
            $booking = Booking::findOrFail($booking_id);
            $this->authorize('update', $booking);
            $validatedData = $request->validated();
            $property = $booking->property;


            $request_start = isset($validatedData['start_date']) ? Carbon::parse($validatedData['start_date']) : Carbon::parse($booking->start_date);
            $request_end = isset($validatedData['end_date']) ? Carbon::parse($validatedData['end_date']) : Carbon::parse($booking->end_date);


            $propertyStatus = $property->bookings()->where('id', '!=', $booking->id)
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


            $booking->update($validatedData);

            return response()->json([
                'message' => 'Operation Completed Successfully',
                'booking' => $booking
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'booking not found', 'message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => 'you are not allowed to update this booking', 'message' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return response()->json(['error' => 'error happened while  booking', 'message' => $e->getMessage()], 500);
        }
    }


    public function delete($booking_id)
    {
        try {

            $booking = Booking::findOrFail($booking_id);
            $this->authorize('delete', $booking);
            $property = $booking->property;
            $booking->delete();

            $hasOtherBookings = $property->bookings()->exists();

            if (!$hasOtherBookings) $property->update(['is_available' => true]);


            return response()->json([
                'message' => 'Booking deleted successfully',
                'property_status' => $property->is_available,
                'property' => $property
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'booking not found', 'message' => $e->getMessage()], 404);
        } catch (AuthorizationException $e) {
            return response()->json(['error' => 'you are not allowed to delete this booking', 'message' => $e->getMessage()], 403);
        } catch (Exception $e) {
            return response()->json(['error' => 'error happened while deleting  booking', 'message' => $e->getMessage()], 500);
        }
    }
    public function getAllBookings(Request $request)
    {
        $user = $request->user();
        try {
            $bookings = Booking::withTrashed()->where('user_id', $user->id)->get()->map(function ($booking) {
                return [
                    'booking_id' => $booking->id,
                    'property_id' => $booking->property_id,
                    'user_id' => $booking->user_id,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'is_deleted' => $booking->trashed()
                ];
            });
            if ($bookings->isEmpty()) {
                return response()->json([
                    'message' => 'This user has no bookings',
                    'bookings' => []
                ], 200);
            }
            return response()->json([
                'message' => "These are all bookings related to this user",
                'bookings' => $bookings
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
