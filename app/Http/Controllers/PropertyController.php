<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\PropertyImage;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    use AuthorizesRequests;

    public function store(StorePropertyRequest $request)
    {
        $validated = $request->validated();

        $property = Property::create([
            'city'          => $validated['city'],
            'governorate'   => $validated['governorate'],
            'price_per_day' => $validated['price_per_day'],
            'description'   => $validated['description'],
            'user_id'       => $request->user()->id,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $img) {

                $path = $img->store('properties/' . $property->id, 'public');

                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path'  => $path,
                    'is_main'     => $index === 0
                ]);
            }
        }

        return response()->json([
            'message' => 'Property created successfully',
            'property' => $property->load('images')
        ], 201);
    }



    // update function not working || fix it and add delete function to
    // add features table to the property and link it
    public function updateInfo($property_id, UpdatePropertyRequest $request)
    {

        $validatedData = $request->validated();
        $property = Property::findOrFail($property_id);
        $this->authorize('update', $property);

        $property->update($validatedData);

        return response()->json(['message' => 'property informations updated successfuly', 'property' => $property], 200);
    }


    public function addImages(Request $request, $property_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('modifyImages', $property);
        $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,jpg,png,gif|max:5120'
        ]);

        foreach ($request->file('images') as $image) {
            $path = $image->store("properties/{$property->id}", 'public');

            PropertyImage::create([
                'property_id' => $property->id,
                'image_path' => $path,
                'is_main' => false,
            ]);
        }

        return response()->json([
            'message' => 'Images added successfully.',
            'property' => $property->load('images'),
        ], 201);
    }


    public function replaceImage(Request $request, $property_id, $image_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('modifyImages', $property);

        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png|max:5120'
        ]);


        $image = PropertyImage::where('property_id', $property_id)->where('id', $image_id)->firstOrFail();

        // delete the old image
        Storage::disk('public')->delete($image->image_path);

        // Upload new image
        $path = $request->file('image')->store("properties/{$property_id}", 'public');

        $image->update(['image_path' => $path]);

        return response()->json([
            'message' => 'Image replaced successfully.',
            'property' => $property->load('images'),
        ], 200);
    }



    public function deleteImage($property_id, $image_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('modifyImages', $property);

        $image = PropertyImage::where('property_id', $property_id)
            ->where('id', $image_id)
            ->firstOrFail();

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully.',
            'property' => $property->load('images'),
        ]);
    }


    public function setMainImage($property_id, $image_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('modifyImages', $property);

        $image = PropertyImage::where('property_id', $property_id)
            ->where('id', $image_id)
            ->firstOrFail();

        // Reset all images
        PropertyImage::where('property_id', $property_id)->update(['is_main' => false]);

        // Set this one as main
        $image->update(['is_main' => true]);

        return response()->json([
            'message' => 'Main image updated.',
            'property' => $property->load('images'),
        ]);
    }


    public function destroy($property_id)
    {
        $property = Property::findOrFail($property_id);

        $this->authorize('delete', $property);

        // Delete all images from storage
        foreach ($property->images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }

        // Delete all image records
        PropertyImage::where('property_id', $property_id)->delete();

        // Delete the property itself
        $property->delete();

        return response()->json([
            'message' => 'Property deleted successfully.'
        ], 200);
    }



    public function showProperties()
    {
        $properties = Property::with('user')->get();

        return response()->json([
            'properties' => $properties
        ], 200);
    }
}
