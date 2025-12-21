<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Property;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    use AuthorizesRequests;

    /* ===================== CREATE BOOKING ===================== */
    public function store(StoreBookingRequest $request, $property_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('add', $property);

        $data = $request->validated();

        $start = Carbon::parse($data['start_date']);
        $end   = Carbon::parse($data['end_date']);

        $isBooked = $property->bookings()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<', $start)
                            ->where('end_date', '>', $end);
                    });
            })->exists();

        if ($isBooked) {
            return response()->json([
                'message' => 'This property is already booked for the selected dates'
            ], 422);
        }

        $days = $start->diffInDays($end) + 1;

        $data['price']       = $property->price_per_day * $days;
        $data['user_id']     = $request->user()->id;
        $data['property_id'] = $property->id;
        $data['card_number'] = substr($data['card_number'], -4);

        $booking = Booking::create($data);

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking
        ], 201);
    }

    /* ===================== UPDATE BOOKING ===================== */
    public function update(UpdateBookingRequest $request, $booking_id)
    {
        $booking = Booking::findOrFail($booking_id);
        $this->authorize('update', $booking);

        $data = $request->validated();
        $property = $booking->property;

        $start = isset($data['start_date']) ? Carbon::parse($data['start_date']) : Carbon::parse($booking->start_date);
        $end   = isset($data['end_date']) ? Carbon::parse($data['end_date']) : Carbon::parse($booking->end_date);

        $conflict = $property->bookings()
            ->where('id', '!=', $booking->id)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<', $start)
                            ->where('end_date', '>', $end);
                    });
            })->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'This property is already booked for the selected dates'
            ], 422);
        }

        $days = $start->diffInDays($end) + 1;
        $data['price'] = $property->price_per_day * $days;

        $booking->update($data);

        return response()->json([
            'message' => 'Booking updated successfully',
            'booking' => $booking
        ]);
    }

    /* ===================== DELETE BOOKING ===================== */
    public function delete($booking_id)
    {
        $booking = Booking::findOrFail($booking_id);
        $this->authorize('delete', $booking);

        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully'
        ]);
    }

    /* ===================== ALL BOOKINGS ===================== */
    public function getAllBookings()
    {
        $user = Auth::user();

        $bookings = Booking::withTrashed()
            ->with('rating')
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    /* ===================== CANCELLED BOOKINGS ===================== */
    public function getCancelledBookings()
    {
        $user = Auth::user();

        $bookings = Booking::onlyTrashed()
            ->with('rating')
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    /* ===================== CURRENT BOOKINGS ===================== */
    public function getCurrentBookings()
    {
        $user = Auth::user();

        $bookings = Booking::with('rating')
            ->where('user_id', $user->id)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get();

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    /* ===================== FUTURE BOOKINGS ===================== */
    public function getFutureBookings()
    {
        $user = Auth::user();

        $bookings = Booking::with('rating')
            ->where('user_id', $user->id)
            ->where('start_date', '>', now())
            ->get();

        return response()->json([
            'bookings' => $bookings
        ]);
    }

    /* ===================== RATE BOOKING ===================== */
    public function addRating(Request $request, Booking $booking)
    {
        $this->authorize('rate', $booking);

        $data = $request->validate([
            'stars'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $rating = $booking->rating()->create($data);

        return response()->json([
            'rating' => $rating
        ], 201);
    }

    public function updateRating(Request $request, Booking $booking)
    {
        $this->authorize('editrate', $booking);

        if (!$booking->rating) {
            return response()->json(['message' => 'No rating found'], 404);
        }

        $data = $request->validate([
            'stars'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $booking->rating()->update($data);

        return response()->json([
            'rating' => $booking->rating
        ]);
    }
}
