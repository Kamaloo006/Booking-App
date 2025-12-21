<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'price_per_day', 'city', 'user_id', 'description', 'governorate', 'category', 'is_available'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    protected $appends = ['main_image_url'];

    public function images()
    {
        return $this->hasMany(PropertyImage::class)
            ->orderByDesc('is_main')
            ->orderBy('id');
    }

    public function features()
    {
        return $this->hasOne(Feature::class);
    }

    public function getMainImageUrlAttribute()
    {
        $main = $this->images()->where('is_main', true)->first();

        return $main ? $main->url : null;
    }

    // function to get the Main image
    public function mainImage()
    {
        return $this->hasOne(PropertyImage::class)->where('is_main', true);
    }
    public function favorites(){
        return $this->hasMany(Favorite::class);
    }
}
