<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class rating extends Model
{
    protected $fillable = [
        'property_id',
        'stars',
        'comment',
        'user_id'
    ];
    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
