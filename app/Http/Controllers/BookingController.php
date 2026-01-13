<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\ValidatedData;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;


class BookingController extends Controller
{
    use AuthorizesRequests;


    protected $notificationService;


    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }


    private function updatePropertyAvailability(Property $property)
    {
        $hasCurrentBooking = $property->bookings()
            ->where('status', 'accepted')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->exists();

        $property->update([
            'is_available' => !$hasCurrentBooking
        ]);
    }


    public function store(StoreBookingRequest $request,  $property_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('add', $property);
        $validatedData = $request->validated();


        $request_start = Carbon::parse($validatedData['start_date']);
        $request_end   = Carbon::parse($validatedData['end_date']);

        $propertyStatus = $property->bookings()
        ->where('status', 'accepted')
            ->where('end_date', '>=', now()) // ignore past booking
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
                'message' => 'This property is already booked for the selected dates',
            ], 422);
        }

        $user = $request->user();

        $days = $request_start->diffInDays($request_end) + 1;
        $total_price = $property->price_per_day * $days;
        $validatedData['price']       = $total_price;
        $validatedData['user_id']     = $request->user()->id;
        $validatedData['property_id'] = $property->id;
        $validatedData['card_number'] = substr($validatedData['card_number'], -4);
        $validatedData['status'] = 'pending';

        $booking = Booking::create($validatedData);
        $bookingA = Booking::with('property')->find($booking->id);

        if ($user->fcm_token) {
            $this->notificationService->send(
                $user->fcm_token,
                'Booking sent',
                'Waiting for the owner to accept the booking'
            );
        }
       $owner = $property->user;

if ($owner && $owner->fcm_token) {
    $this->notificationService->send(
        $owner->fcm_token,
        'New Booking Request',
        'You have received a new booking request for your property',
        [
            'type' => 'new_booking',
            'booking_id' => $booking->id,
            'property_id' => $property->id,
        ]
    );
}



        return response()->json([
            'message' => 'Operation Completed Successfully. Waiting for the owner to accept the booking',
            'booking' => $bookingA
        ], 201);
    }


    // get all pending bookings by owner

    public function getAllPendingBookings()
    {
        $owner = Auth::user();

        $bookings = Booking::where('status', 'pending')
         //->where('end_date', '>=', now())
            ->whereHas('property', function ($q) use ($owner) {
                $q->where('user_id', $owner->id);
            })
            ->with(['property.images', 'user'])
            ->get();

        return response()->json([
            'message' => 'Owner pending bookings fetched successfully',
            'bookings' => $bookings
        ], 200);
    }


    public function acceptBooking($booking_id)
    {
        $owner = Auth::user();

        $booking = Booking::where('id', $booking_id)
            ->where('status', 'pending')
            ->whereHas('property', fn($q) => $q->where('user_id', $owner->id))
            ->firstOrFail();

        $start = $booking->start_date;
        $end   = $booking->end_date;

        // 🔥 التحقق من التعارض قبل القبول فقط
        $conflict = Booking::where('property_id', $booking->property_id)
            ->where('id', '!=', $booking->id)
                ->where('end_date', '>=', now()) // ignore past bookings
            ->whereIn('status', ['accepted', 'pending_edit']) // pending لا يمنع.. فقط accepted & pending_edit
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->where('start_date', '<', $start)
                            ->where('end_date', '>', $end);
                    });
            })
            ->exists();

        if ($conflict) {
            // ❗️ لا نقبل → فقط نرفض أو نرجع Response بدون update

            return response()->json(['message' => 'Booking rejected due to date conflict'], 422);
        }

        // ✔️ قبول إذا لا يوجد تعارض
        $booking->update(['status' => 'accepted']);
        $this->updatePropertyAvailability($booking->property);

        // 🔔 Send notification to the user who booked
        if ($booking->user && $booking->user->fcm_token) {
            $this->notificationService->send(
                $booking->user->fcm_token,
                'Booking Accepted',
                "Your booking for {$booking->property->name} has been accepted.",
                [
                    'type' => 'booking_status',
                    'status' => 'accepted',
                    'booking_id' => $booking->id
                ]
            );
        }
        
        
        return response()->json([
            'message' => 'Booking accepted successfully',
            'booking' => $booking
        ]);
    }




    public function getOwnerCurrentBookings()
    {
        $owner = Auth::user();
        $today = now();

        $bookings = Booking::where('status', 'accepted')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->whereHas('property', function ($q) use ($owner) {
                $q->where('user_id', $owner->id);
            })
            ->with([
                'property.images',
                'user'
            ])
            ->get()
            ->map(function ($booking) {
                return [
                    'id'  => $booking->id,
                    'start_date'  => $booking->start_date,
                    'end_date'    => $booking->end_date,
                    'price'       => $booking->price,
                    'status'      => $booking->status,
                    'user'        => $booking->user,
                    'property'    => $booking->property,
                ];
            });

        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'No current bookings found for your properties',
                'bookings' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Current bookings for your properties',
            'bookings' => $bookings
        ], 200);
    }




    public function rejectBooking($id)
    {
        $owner = Auth::user();

        $booking = Booking::where('id', $id)
            ->where('status', 'pending')
            ->whereHas('property', function ($q) use ($owner) {
                $q->where('user_id', $owner->id);
            })
            ->with(['property.images', 'user'])
            ->firstOrFail();

        $booking->update([
            'status' => 'rejected'
        ]);

        // 🔔 Send notification to the user who booked
        if ($booking->user && $booking->user->fcm_token) {
            $this->notificationService->send(
                $booking->user->fcm_token,
                'Booking Rejected',
                "Your booking for {$booking->property->name} has been rejected.",
                [
                    'type' => 'booking_status',
                    'status' => 'rejected',
                    'booking_id' => $booking->id
                ]
            );
        }

        return response()->json([
            'message' => 'Booking rejected successfully',
            'booking' => $booking
        ], 200);
    }


    public function getAcceptedBookings()
    {
        $owner = Auth::user();

        $bookings = Booking::where('status', 'accepted')
            ->whereHas('property', function ($q) use ($owner) {
                $q->where('user_id', $owner->id);
            })
            ->with(['property.images', 'user'])
            ->get();

        return response()->json([
            'message' => 'Accepted bookings fetched successfully',
            'bookings' => $bookings
        ], 200);
    }

    public function getRejectedBookings()
    {
        $owner = Auth::user();

        $bookings = Booking::where('status', 'rejected')
            ->whereHas('property', function ($q) use ($owner) {
                $q->where('user_id', $owner->id);
            })
            ->with(['property.images', 'user'])
            ->get();

        return response()->json([
            'message' => 'Rejected bookings fetched successfully',
            'bookings' => $bookings
        ], 200);
    }




    public function update(UpdateBookingRequest $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('update', $booking);

        if ($booking->status !== 'accepted') {
            return response()->json([
                'message' => 'Only accepted bookings can be edited'
            ], 422);
        }

        $today = now()->startOfDay();

        $isCurrent = $booking->start_date <= $today && $booking->end_date >= $today;
        $isFuture  = $booking->start_date > $today;



        if ($isCurrent) {

            if ($request->has('start_date')) {
                return response()->json([
                    'message' => 'Cannot edit start date for a current booking'
                ], 422);
            }

            $start = Carbon::parse($booking->start_date);
            $end   = Carbon::parse($request->end_date);
        } elseif ($isFuture) {
            // ✅ مسموح تعديل الاثنين
            $start = Carbon::parse($request->start_date);
            $end   = Carbon::parse($request->end_date);
        } else {
            return response()->json([
                'message' => 'Invalid booking state'
            ], 422);
        }

        if ($end <= $start) {
            return response()->json([
                'message' => 'End date must be after start date'
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | تحقق من التضارب مع الحجوزات المقبولة فقط
    |--------------------------------------------------------------------------
    */

        $conflict = $booking->property->bookings()
            ->where('id', '!=', $booking->id)
            ->where('status', 'accepted')
                ->where('end_date', '>=', now()) // ignore past bookings
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
                'message' => 'Date conflict with another accepted booking'
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | حساب السعر الجديد وتخزين التعديل
    |--------------------------------------------------------------------------
    */





        $days  = $start->diffInDays($end) + 1;
        $price = $days * $booking->property->price_per_day;

        $booking->update([
            'edit_start_date' => $start->format('Y-m-d'),
            'edit_end_date'   => $end->format('Y-m-d'),
            'edit_price'      => $price,
            'status'          => 'pending_edit',
        ]);

        $renter = $booking->user;              // booking owner (renter)
$owner  = $booking->property->user;    // property owner

// Notify renter
if ($renter && $renter->fcm_token) {
    $this->notificationService->send(
        $renter->fcm_token,
        'Edit Request Sent',
        'Your booking edit request was sent to the owner and is waiting for approval',
        [
            'type' => 'booking_edit',
            'booking_id' => $booking->id,
            'status' => 'pending_edit',
        ]
    );
}

// Notify owner
if ($owner && $owner->fcm_token) {
    $this->notificationService->send(
        $owner->fcm_token,
        'Booking Edit Request',
        'A renter has requested to edit an existing booking',
        [
            'type' => 'booking_edit_request',
            'booking_id' => $booking->id,
            'property_id' => $booking->property_id,
        ]
    );
}

        return response()->json([
            'message' => 'Edit request sent to owner',
            'booking' => $booking
        ]);
    }

    public function getPendingEditBookings()
    {
        $owner = Auth::user();

        $bookings = Booking::where('status', 'pending_edit')
            ->whereHas('property', function ($q) use ($owner) {
                $q->where('user_id', $owner->id);
            })
            ->with(['property.images', 'user'])
            ->get();

        return response()->json([
            'message' => 'Owner pending bookings fetched successfully',
            'bookings' => $bookings
        ], 200);
    }



   public function acceptEdit(int $id)
{
    $owner = Auth::user();

    // Find the booking in pending_edit status
    $booking = Booking::where('id', $id)
        ->where('status', 'pending_edit')
        ->whereHas('property', fn($q) => $q->where('user_id', $owner->id))
        ->firstOrFail();

    $start = Carbon::parse($booking->edit_start_date);
    $end   = Carbon::parse($booking->edit_end_date);

    // Check for conflicts with other accepted or pending_edit bookings
    $conflict = Booking::where('property_id', $booking->property_id)
        ->where('id', '!=', $booking->id)
            ->where('end_date', '>=', now()) // ignore past bookings
        ->whereIn('status', ['accepted', 'pending_edit'])
        ->where(function ($q) use ($start, $end) {
            $q->where(function ($x) use ($start, $end) {
                // Conflicts with accepted bookings
                $x->where('status', 'accepted')
                  ->where(function ($y) use ($start, $end) {
                      $y->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function ($z) use ($start, $end) {
                            $z->where('start_date', '<', $start)
                              ->where('end_date', '>', $end);
                        });
                  });
            })->orWhere(function ($x) use ($start, $end) {
                // Conflicts with pending edit bookings
                $x->where('status', 'pending_edit')
                  ->where(function ($y) use ($start, $end) {
                      $y->whereBetween('edit_start_date', [$start, $end])
                        ->orWhereBetween('edit_end_date', [$start, $end])
                        ->orWhere(function ($z) use ($start, $end) {
                            $z->where('edit_start_date', '<', $start)
                              ->where('edit_end_date', '>', $end);
                        });
                  });
            });
        })
        ->exists();

    if ($conflict) {
        return response()->json([
            'message' => 'Cannot accept edit due to date conflict'
        ], 422);
    }

    // Update booking with edited dates and price
    $booking->update([
        'start_date'      => $booking->edit_start_date,
        'end_date'        => $booking->edit_end_date,
        'price'           => $booking->edit_price,
        'edit_start_date' => null,
        'edit_end_date'   => null,
        'edit_price'      => null,
        'status'          => 'accepted',
    ]);

    $this->updatePropertyAvailability($booking->property);

    // Notify the renter
    if ($booking->user && $booking->user->fcm_token) {
        $this->notificationService->send(
            $booking->user->fcm_token,
            'Booking Edit Accepted',
            "Your booking modification for {$booking->property->name} has been accepted.",
            [
                'type' => 'booking_status',
                'status' => 'accepted',
                'booking_id' => $booking->id
            ]
        );
    }

    return response()->json([
        'message' => 'Edit accepted successfully',
        'booking' => $booking
    ], 200);
}




    public function rejectEdit(int $id)
    {
        $owner = Auth::user();

        $booking = Booking::with('property')->find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        if ($booking->status !== 'pending_edit') {
            return response()->json([
                'message' => 'This booking is not in pending edit state',
                'status'  => $booking->status
            ], 422);
        }

        if ($booking->property->user_id !== $owner->id) {
            return response()->json([
                'message' => 'Unauthorized action'
            ], 403);
        }

        $booking->update([
            'edit_start_date' => null,
            'edit_end_date'   => null,
            'edit_price'      => null,
            'status'          => 'accepted',
        ]);

        $this->updatePropertyAvailability($booking->property);

        
        if ($booking->user && $booking->user->fcm_token) {
            $this->notificationService->send(
                $booking->user->fcm_token,
                'Booking Edit rejected',
                "Your booking modification for {$booking->property->name} has been rejected.",
                [
                    'type' => 'booking_status',
                    'status' => 'rejected',
                    'booking_id' => $booking->id
                ]
            );
        }

        return response()->json([
            'message' => 'Edit rejected, booking restored successfully',
            'booking' => $booking
        ], 200);
    }

    public function delete($id)
    {
        $booking = Booking::findOrFail($id);
        $this->authorize('delete', $booking);
        $property = $booking->property;
        $booking->delete();

        $this->updatePropertyAvailability($property);


        // if (!$hasOtherBookings) $property->update(['is_available' => true]);
        return response()->json([
            'message' => 'Booking deleted successfully',
            'property_status' => $property->is_available,
            'property' => $property
        ], 200);
    }

    public function getAllBookings(Request $request)
    {
        $user = $request->user();
        $bookings = Booking::withTrashed()->with(['property'])->where('status', 'accepted')->where('user_id', $user->id)->get()->map(function ($booking) {
            return [
                'id' => $booking->id,
                'property_id' => $booking->property_id,
                'user_id' => $booking->user_id,
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'status' => $booking->status,
                'price'    => $booking->price,
                'is_deleted' => $booking->trashed(),
                'property' => $booking->property,
               
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
    }

    public function getCancelledBookings()
    {
        $user = Auth::user();
        $bookings = Booking::onlyTrashed()->with('property')->where('user_id', $user->id)->get()->map(function ($booking) {
            return [
                'id' => $booking->id,
                'property_id' => $booking->property_id,
                'user_id' => $booking->user_id,
                'start_date' => $booking->start_date,
                'end_date' => $booking->end_date,
                'status' => $booking->status,
                'is_deleted' => $booking->trashed(),
                'property' => $booking->property
            ];
        });
        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'This user has no bookings',
                'bookings' => []
            ], 200);
        }
        return response()->json([
            'message' => "These are all cancelled bookings related to this user",
            'bookings' => $bookings
        ], 200);
    }


    public function getOldBookings()
    {
        $user = Auth::user();

        $bookings = Booking::with(['property'])->where('user_id', $user->id)
            // ->where('end_date', '<', now())
            // ->get()
             ->where('status', 'accepted') // only accepted bookings
        ->where('end_date', '<', now()) // date in the past
        ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'property_id' => $booking->property_id,
                    'user_id' => $booking->user_id,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'status' => $booking->status,
                    'is_deleted' => $booking->trashed(),
                    'property' => $booking->property,
                ];
            });

        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'This user has no old bookings',
                'bookings' => []
            ], 200);
        }

        return response()->json([
            'message' => 'These are all old bookings for this user',
            'bookings' => $bookings
        ], 200);
    }


    public function getCurrentBookings()
    {
        $user = Auth::user();

        $bookings = Booking::with(['property'])->where('user_id', $user->id)
            ->where('end_date', '>=', now())
            ->where('start_date', '<=', now())
            ->where('status', 'accepted')
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'property_id' => $booking->property_id,
                    'user_id' => $booking->user_id,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'status' => $booking->status,
                    'is_deleted' => $booking->trashed(),
                    'property' => $booking->property

                ];
            });

        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'This user has no current bookings',
                'bookings' => []
            ], 200);
        }

        return response()->json([
            'message' => 'These are all current bookings for this user',
            'bookings' => $bookings
        ], 200);
    }


    public function getFutureBookings()
    {
        $user = Auth::user();

        $bookings = Booking::with(['property'])->where('status', 'accepted')->where('user_id', $user->id)
            ->where('start_date', '>', now())
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'property_id' => $booking->property_id,
                    'user_id' => $booking->user_id,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'status' => $booking->status,
                    'is_deleted' => $booking->trashed(),
                    'property' => $booking->property
                ];
            });

        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'This user has no future bookings',
                'bookings' => []
            ], 200);
        }
        return response()->json([
            'message' => 'These are all future bookings for this user',
            'bookings' => $bookings
        ], 200);
    }


    public function getCurrentAndFutureBookings()
    {
        $user = Auth::user();

        $bookings = Booking::with('property')->where('user_id', $user->id)->where('status', 'accepted')
            ->where('end_date', '>=', now())
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'property_id' => $booking->property_id,
                    'user_id' => $booking->user_id,
                    'start_date' => $booking->start_date,
                    'end_date' => $booking->end_date,
                    'status' => $booking->status,
                    'is_deleted' => $booking->trashed(),
                    'property' => $booking->property,

                ];
            });

        if ($bookings->isEmpty()) {
            return response()->json([
                'message' => 'This user has no future bookings',
                'bookings' => []
            ], 200);
        }

        return response()->json([
            'message' => 'These are all future bookings for this user',
            'bookings' => $bookings
        ], 200);
    }



    public function getMyPendingBookings()
    {
        $user = Auth::user();

        $bookings = Booking::where('user_id', $user->id)->where('status', 'pending')->with('property')->get();
        return response()->json([
            'message' => 'Your pending bookings',
            'bookings' => $bookings
        ]);
    }

    public function getMyPendingEditBookings()
    {
        $user = Auth::user();

        $bookings = Booking::where('user_id', $user->id)->where('status', 'pending_edit')->with('property')->get();
        return response()->json([
            'message' => 'Your pending bookings',
            'bookings' => $bookings
        ]);
    }
}
