<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function store(StorePropertyRequest $request)
    {
        $validateData = $request->validated();
        $validateData['user_id'] = $request->user()->id;
        $property = Property::create($validateData);
        return response()->json([
            'message' => 'Operation Completed Successfully',
            'property' => $property
        ], 201);
    }
}
