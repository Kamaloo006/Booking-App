<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{

    protected $fillable = ['price_per_day', 'city', 'user_id', 'description', 'governorate'];
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
            ->orderByDesc('is_main') // اجعل الرئيسية تظهر أول واحدة
            ->orderBy('id');
    }

    // إرجاع رابط الصورة الرئيسية
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
}
