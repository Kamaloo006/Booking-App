<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'price_per_day', 'city', 'user_id', 'description', 
        'governorate', 'category', 'is_available', 'rooms', 'kitchens', 
        'area', 'bathrooms'
    ];

    protected $appends = ['main_image_url', 'average_rating' , 'ratings_count'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function images()
    {
        return $this->hasMany(PropertyImage::class)
            ->orderByDesc('is_main')
            ->orderBy('id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function userRating()
    {
        return $this->hasOne(Rating::class)->where('user_id', auth()->id());
    }

    // Accessors
    public function getMainImageUrlAttribute()
    {
        $main = $this->images()->where('is_main', true)->first();
        return $main ? $main->url : null;
    }

   public function getAverageRatingAttribute()
{
    $avg = $this->ratings()->avg('stars');
    return $avg !== null ? round($avg, 1) : null; // e.g., 4.3
}
    public function getRatingsCountAttribute()
{
    return $this->ratings()->count();
}
public function favoritedBy()
{
    return $this->belongsToMany(
        User::class,
        'favorites'
    )->withTimestamps();
}

}
