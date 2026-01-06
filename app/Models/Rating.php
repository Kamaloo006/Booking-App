<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'property_id',
        'stars',
        'comment',
        'user_id'
    ];
 protected $casts = [
        'stars' => 'float',
    ];
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}

