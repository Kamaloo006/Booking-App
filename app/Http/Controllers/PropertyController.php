<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyFeaturesReqeust;
use Illuminate\Http\Request;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\PropertyImage;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PropertyController extends Controller
{
    use AuthorizesRequests;


    public function showProperty($property_id)
    {
        $property = Property::findOrFail($property_id);
        return response()->json(['property' => $property->load('images')]);
    }

    public function store(StorePropertyRequest $request)
    {
        $validated = $request->validated();

        $property = Property::create([
            'city'          => $validated['city'],
            'name'          => $validated['name'],
            'governorate'   => $validated['governorate'],
            'price_per_day' => $validated['price_per_day'],
            'description'   => $validated['description'],
            'category'      => $validated['category'],
            'is_available'  => $validated['is_available'],
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

    public function storeFeatures(StorePropertyFeaturesReqeust $request, $property_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('update', $property);
        $validatedData = $request->validated();

        if ($property->user_id !== Auth::user()->id) {
            return response()->json(['message' => 'you not authorized to add features'], 403);
        }

        if ($property->features) $property->features->update($validatedData);

        $property->features()->create($validatedData);

        return response()->json([
            'message' => 'Features saved successfully.',
            'property' => $property->load('features'),
        ], 200);
    }

    // تزبيط الفلترة مع اكتر من وحدة


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


    public function filterProperties(Request $request)
    {
        $query = Property::with(['user', 'features']);

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('governorate')) {
            $query->where('governorate', $request->governorate);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('min_price')) {
            $query->where('price_per_day', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price_per_day', '<=', $request->max_price);
        }

        if ($request->filled('is_available')) {
            $query->where('is_available', $request->is_available);
        }

        // features filter
        $query->whereHas('features', function ($f) use ($request) {

            if ($request->filled('rooms')) {
                $f->where('rooms', '>=', $request->rooms);
            }

            if ($request->filled('bathrooms')) {
                $f->where('bathrooms', '>=', $request->bathrooms);
            }

            if ($request->filled('kitchens')) {
                $f->where('kitchens', '>=', $request->kitchens);
            }

            if ($request->filled('min_area')) {
                $f->where('area', '>=', $request->min_area);
            }

            if ($request->filled('max_area')) {
                $f->where('area', '<=', $request->max_area);
            }
        });

        $properties = $query->get();

        return response()->json([
            'message' => 'Filtered properties retrieved successfully.',
            'properties' => $properties
        ], 200);
    }
}
