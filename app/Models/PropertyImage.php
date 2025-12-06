<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyImage extends Model
{
    protected $fillable = [
        'property_id',
        'image_path',
        'is_main'
    ];

    protected $appends = ['url'];


    public function getUrlAttribute()
    {
        if (!$this->image_path) {
            return null;
        }

        return url('storage/' . $this->image_path);
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
