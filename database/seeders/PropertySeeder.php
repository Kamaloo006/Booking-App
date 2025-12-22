<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PropertySeeder extends Seeder
{
    public function run(): void
    {
        Property::query()->delete();

        $owners = User::where('role', 'owner')->get();

        if ($owners->isEmpty()) {
            $this->command->error('No owner users found. Please seed owners first.');
            return;
        }

        $properties = [
            [
                'name' => 'Luxury Villa with Pool',
                'category' => 'villa',
                'governorate' => 'Damascus',
                'city' => 'Mazzeh',
                'price_per_day' => 150,
                'description' => 'A sophisticated villa crafted for modern lifestyles, featuring elegant architectural details, expansive living spaces, and a private outdoor area designed for relaxation',
                'rooms' => 5,

                'bathrooms' => 4,
                'kitchens' => 1,
                'area' => 350,
                'folder' => 'property_1',
                'is_available' => true,
            ],
            [
                'name' => 'Modern Apartment',
                'category' => 'apartment',
                'governorate' => 'Aleppo',
                'city' => 'Azaz',
                'price_per_day' => 70,
                'description' => 'A modern apartment designed with contemporary elegance, offering a well-balanced combination of style and functionality.',
                'rooms' => 3,
                'bathrooms' => 2,
                'kitchens' => 1,
                'area' => 120,
                'folder' => 'property_2',
                'is_available' => true,
            ],
            [
                'name' => 'Family House',
                'category' => 'house',
                'governorate' => 'Latakia',
                'city' => 'Jableh',
                'price_per_day' => 90,
                'description' => 'A perfect family house with a spacious and practical layout, thoughtfully designed to accommodate everyday living and family gatherings',
                'rooms' => 4,

                'bathrooms' => 2,
                'kitchens' => 1,
                'area' => 200,
                'folder' => 'property_3',
                'is_available' => true,
            ],
            [
                'name' => 'Beachfront Apartment',
                'category' => 'apartment',
                'governorate' => 'Tartus',
                'city' => 'Safita',
                'price_per_day' => 110,
                'description' => 'An apartment offering a stunning sea view that creates a calm and refreshing living experience.',
                'rooms' => 2,

                'bathrooms' => 2,
                'kitchens' => 1,
                'area' => 100,
                'folder' => 'property_4',
                'is_available' => true,
            ],
            [
                'name' => 'Country Style Villa',
                'category' => 'villa',
                'governorate' => 'Idlib',
                'city' => 'Saraqib',
                'price_per_day' => 130,
                'description' => 'A spacious villa with countryside charm, blending traditional character with modern comfort',
                'rooms' => 6,

                'bathrooms' => 3,
                'kitchens' => 1,
                'area' => 280,
                'folder' => 'property_5',
                'is_available' => false,
            ],
        ];

        foreach ($properties as $index => $item) {
            $owner = $owners[$index % $owners->count()];

            $property = Property::create([
                'name'          => $item['name'],
                'category'      => $item['category'],
                'governorate'   => $item['governorate'],
                'city'          => $item['city'],
                'price_per_day' => $item['price_per_day'],
                'description'   => $item['description'],
                'is_available'  => $item['is_available'],
                'rooms'         => $item['rooms'],
                'bathrooms'     => $item['bathrooms'],
                'kitchens'      => $item['kitchens'],
                'area'          => $item['area'],
                'user_id'       => $owner->id,
            ]);

            // Images
            $basePath = "demo/properties/{$item['folder']}";

            if (Storage::disk('public')->exists($basePath)) {
                $property->images()->create([
                    'image_path' => "$basePath/main.jpg",
                    'is_main' => true,
                ]);

                foreach (['interior_1.jpg', 'interior_2.jpg', 'interior_3.jpg'] as $img) {
                    if (Storage::disk('public')->exists("$basePath/$img")) {
                        $property->images()->create([
                            'image_path' => "$basePath/$img",
                            'is_main' => false,
                        ]);
                    }
                }
            }
        }

        $this->command->info('Properties seeded successfully!');
    }
}
