<?php

namespace App\Providers;

use App\Models\Booking;
use App\Policies\BookingPolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    protected $policies=[
        Booking::class=>BookingPolicy::class,
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
