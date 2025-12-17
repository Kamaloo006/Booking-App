<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Carbon\Carbon;



class BookingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Booking $booking)
    {
        if ($user->id != $booking->user_id) {
            return Response::deny('This booking does not belong to you');
        }
        if (now()->greaterThanOrEqualTo($booking->start_date)) {
            return Response::deny('You cannot modify it because it has already started');
        }
        return Response::allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Booking $booking)
    {
        if ($user->id != $booking->user_id) {
            return Response::deny('This booking does not belong to you');
        }
        if (now()->greaterThanOrEqualTo($booking->start_date)) {
            return Response::deny('You cannot delete it because it has already started');
        }
        return Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Booking $booking): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Booking $booking): bool
    {
        return false;
    }

    public function rate(User $user, Booking $booking)
    {
        if ($booking->user_id != $user->id) {
            return Response::deny('This booking does not belong to you');
        }
        if (Carbon::parse($booking->end_date)->isFuture()) {
            return Response::deny('You cannot rate it until it has finished');
        }
        if ($booking->rating) {
            return Response::deny('This booking has already been rated');
        }
        return Response::allow();
    }
}
