<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PropertyPolicy
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
    public function view(User $user, Property $property)
    {
        if ($user->id != $property->user_id) {
            return Response::deny('This property does not belong to you');
        }
        return Response::allow();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Property $property): bool
    {
        return $user->id === $property->user_id;
    }

    public function add(User $user, Property $property)
    {
        if ($property->user_id == $user->id) {
            return Response::deny('You cannot book your own property');
        }
        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Property $property): bool
    {
        return $user->id === $property->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Property $property): bool
    {
        return $user->id === $property->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Property $property): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Property $property): bool
    {
        return false;
    }

    public function modifyImages(User $user, Property $property)
    {
        return $user->id === $property->user_id;
    }

    /**
     * Determine whether the user can rate the property.
     */
    public function rate(User $user, Property $property)
    {
        $hasBooked = $user->bookings()
            ->where('property_id', $property->id)
            ->where('end_date', '<', now())
            ->exists();

        if (!$hasBooked) {
            return Response::deny('You must have a previous booking for this property to rate it');
        }

        // No need to check "already rated" because controller uses updateOrCreate
        return Response::allow();
    }

    /**
     * Determine whether the user can edit their rating.
     */
    public function editRate(User $user, Property $property)
    {
        $hasRating = $property->ratings()
            ->where('user_id', $user->id)
            ->exists();

        if (!$hasRating) {
            return Response::deny('You have not rated this property yet');
        }

        return Response::allow();
    }
}
