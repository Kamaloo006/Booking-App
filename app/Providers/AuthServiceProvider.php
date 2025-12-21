<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Favorite;
use App\Models\Property;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\FavoritePolicy;
use App\Policies\PropertyPolicy;
use App\Policies\UserPolicy;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    protected $policies = [
        Property::class => PropertyPolicy::class,
        Booking::class => BookingPolicy::class,
        User::class=>UserPolicy::class,
       
    ];
    // public function register(): void
    // {
    //     //
    // }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
