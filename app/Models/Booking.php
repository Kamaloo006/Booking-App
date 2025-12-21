<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id', 'property_id', 'start_date', 'end_date', 'card_last4', 'billing_address', 'price'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
    public function rating()
    {
        return $this->hasOne(Rating::class);
    }
}
