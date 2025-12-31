<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PropertyController extends Controller
{
    use AuthorizesRequests;

    public function showProperty($property_id)
    {
        $property = Property::findOrFail($property_id);
        return response()->json([
            'property' => $property->load('images')
        ]);
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
            'rooms'         => $validated['rooms'],
            'bathrooms'     => $validated['bathrooms'],
            'kitchens'      => $validated['kitchens'],
            'area'          => $validated['area'],
            'user_id'       => $request->user()->id,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $img) {
                $path = $img->store("properties/{$property->id}", 'public');
                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path'  => $path,
                    'is_main'     => $index === 0
                ]);
            }
        }

        return response()->json([
            'message'  => 'Property created successfully',
            'property' => $property->load('images')
        ], 201);
    }

    public function updateInfo($property_id, UpdatePropertyRequest $request)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('update', $property);

        $property->update($request->validated());

        return response()->json([
            'message'  => 'Property updated successfully',
            'property' => $property->fresh()->load('images')
        ], 200);
    }

    public function addImages(Request $request, $property_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('modifyImages', $property);

        $request->validate([
            'images'   => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,jpg,png,gif|max:5120'
        ]);

        foreach ($request->file('images') as $image) {
            $path = $image->store("properties/{$property->id}", 'public');
            PropertyImage::create([
                'property_id' => $property->id,
                'image_path'  => $path,
                'is_main'     => false,
            ]);
        }

        return response()->json([
            'message'  => 'Images added successfully.',
            'property' => $property->load('images'),
        ], 201);
    }

    public function replaceImage(Request $request, $property_id, $image_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('modifyImages', $property);

        $request->validate(['image' => 'required|image|mimes:jpeg,jpg,png|max:5120']);

        $image = PropertyImage::where('property_id', $property_id)
            ->where('id', $image_id)
            ->firstOrFail();

        Storage::disk('public')->delete($image->image_path);

        $path = $request->file('image')->store("properties/{$property_id}", 'public');
        $image->update(['image_path' => $path]);

        return response()->json([
            'message'  => 'Image replaced successfully.',
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
            'message'  => 'Image deleted successfully.',
            'property' => $property->load('images'),
        ]);
    }

    public function setMainImage($property_id, $image_id)
    {
        try {
            $property = Property::findOrFail($property_id);
            $this->authorize('modifyImages', $property);

            $image = PropertyImage::where('property_id', $property_id)
                ->where('id', $image_id)
                ->firstOrFail();

            PropertyImage::where('property_id', $property_id)->update(['is_main' => false]);
            $image->update(['is_main' => true]);

            return response()->json([
                'message'  => 'Main image updated.',
                'property' => $property->load('images'),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error'   => 'property not found'
            ], 404);
        }
    }

    public function destroy($property_id)
    {
        $property = Property::findOrFail($property_id);
        $this->authorize('delete', $property);

        foreach ($property->images as $img) {
            Storage::disk('public')->delete($img->image_path);
        }
        PropertyImage::where('property_id', $property_id)->delete();
        $property->delete();

        return response()->json(['message' => 'Property deleted successfully.']);
    }

    public function filterProperties(Request $request)
    {
        $query = Property::with(['user', 'images', 'userRating']);

        if ($request->filled('city'))         $query->where('city', $request->city);
        if ($request->filled('governorate'))  $query->where('governorate', $request->governorate);
        if ($request->filled('category'))     $query->where('category', $request->category);
        if ($request->filled('min_price'))    $query->where('price_per_day', '>=', $request->min_price);
        if ($request->filled('max_price'))    $query->where('price_per_day', '<=', $request->max_price);
        if ($request->filled('is_available')) $query->where('is_available', $request->is_available);
        if ($request->filled('rooms'))        $query->where('rooms', $request->rooms);
        if ($request->filled('bathrooms'))    $query->where('bathrooms', $request->bathrooms);
        if ($request->filled('kitchens'))     $query->where('kitchens', $request->kitchens);
        if ($request->filled('min_area'))     $query->where('area', '>=', $request->min_area);
        if ($request->filled('max_area'))     $query->where('area', '<=', $request->max_area);

        $properties = $query->get();

        return response()->json([
            'message'    => 'Filtered properties retrieved successfully.',
            'properties' => $properties
        ]);
    }

    public function getPropertiesByOwner()
    {
        $user = Auth::user();
        $this->authorize('showByOwner', $user);

        $properties = $user->properties()->with('images')->get();

        return response()->json([
            'message'    => 'These all properties for this owner',
            'properties' => $properties
        ]);
    }

    public function getProperty(Property $property)
    {
        $property->load(['images', 'userRating']);

        return response()->json([
            'message'             => 'Operation Completed Successfully',
            'property'            => $property,
            'average_rating'      => $property->average_rating,
            'current_user_rating' => $property->userRating,
        ]);
    }

    public function toggleFavorite(Property $property)
    {
        $user = Auth::user();

        $favorite = $user->favorites()->where('property_id', $property->id)->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['message' => 'Property removed from favorites']);
        }

        $user->favorites()->create(['property_id' => $property->id]);
        return response()->json(['message' => 'Property added to favorites'], 201);
    }

    public function getFavorites()
    {
        $user = Auth::user();
        $favorites = $user->favorites()->with('property.images')->get();

        return response()->json([
            'message'  => 'Operation Completed Successfully',
            'favorite' => $favorites
        ]);
    }

    public function addRating(Request $request, Property $property)
    {
        $this->authorize('rate', $property);

        $request->validate([
            'stars'   => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $rating = $property->ratings()->updateOrCreate(
            ['user_id' => Auth::id()],
            ['stars' => $request->stars, 'comment' => $request->comment]
        );

        $status = $rating->wasRecentlyCreated ? 201 : 200;

        return response()->json([
            'message' => 'Operation Completed Successfully',
            'rating'  => $rating
        ], $status);
    }
}
