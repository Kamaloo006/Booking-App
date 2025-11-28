<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable=['city','price_per_day','user_id','description','governorate'];
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function bookings(){
        return $this->hasMany(Booking::class);
    }
}
