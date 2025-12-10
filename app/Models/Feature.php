<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;
    protected $fillable = ['area', 'rooms', 'kitchens', 'bathrooms'];

    public function property()
    {
        return $this->belongsTo(Property::class);
    }
}
