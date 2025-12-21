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
        // Clean previous properties
        Property::query()->delete();

        // Get all owner users
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
                'description' => 'A sophisticated villa crafted for modern lifestyles, showcasing sleek architecture, tasteful décor, and thoughtfully designed living areas. Ideal for residents seeking both luxury and comfort in a prime location.',
                'features' => [
                    'rooms' => 5,
                    'bathrooms' => 4,
                    'kitchens' => 2,
                    'area' => 350,
                ],
                'folder' => 'property_1',
                'is_available' => true
            ],
            [
                'name' => 'Modern Apartment ',
                'category' => 'apartment',
                'governorate' => 'Aleppo',
                'city' => 'Azaz',
                'price_per_day' => 70,
                'description' => 'An exquisite residence that blends elegance with functionality, offering open-plan spaces, premium materials, and panoramic views. This apartment provides a serene atmosphere while maintaining a contemporary, upscale feel.',
                'features' => [
                    'rooms' => 3,
                    'bathrooms' => 2,
                    'kitchens' => 1,
                    'area' => 120,
                ],
                'folder' => 'property_2',
                'is_available' => true
            ],
            [
                'name' => 'Family House',
                'category' => 'house',
                'governorate' => 'Latakia',
                'city' => 'Jableh',
                'price_per_day' => 90,
                'description' => 'A luxurious property designed for refined living, featuring modern finishes, spacious interiors, and abundant natural light. Every detail reflects comfort, style, and sophistication. Perfect for those who appreciate high-end urban living.',
                'features' => [
                    'rooms' => 4,
                    'bathrooms' => 2,
                    'kitchens' => 1,
                    'area' => 200,
                ],
                'folder' => 'property_3',
                'is_available' => true
            ],
            [
                'name' => 'Beachfront Apartment',
                'category' => 'apartment',
                'governorate' => 'Tartus',
                'city' => 'Safita',
                'price_per_day' => 110,
                'description' => 'An exquisite residence that blends elegance with functionality, offering open-plan spaces, premium materials, and panoramic views. This apartment provides a serene atmosphere while maintaining a contemporary, upscale feel.',
                'features' => [
                    'rooms' => 2,
                    'bathrooms' => 2,
                    'kitchens' => 1,
                    'area' => 100,
                ],
                'folder' => 'property_4',
                'is_available' => true,
            ],
            [
                'name' => 'Country Style Villa',
                'category' => 'villa',
                'governorate' => 'Idlib',
                'city' => 'Saraqib',
                'price_per_day' => 130,
                'description' => 'A luxurious property designed for refined living, featuring modern finishes, spacious interiors, and abundant natural light. Every detail reflects comfort, style, and sophistication. Perfect for those who appreciate high-end urban living.',
                'features' => [
                    'rooms' => 4,
                    'bathrooms' => 3,
                    'kitchens' => 1,
                    'area' => 280,
                ],
                'folder' => 'property_5',
                'is_available' => false,
            ],
        ];

        foreach ($properties as $index => $item) {

            // Assign owner (rotates if more than one)
            $owner = $owners[$index % $owners->count()];

            $property = Property::create([
                'name' => $item['name'],
                'category' => $item['category'],
                'governorate' => $item['governorate'],
                'city' => $item['city'],
                'price_per_day' => $item['price_per_day'],
                'description' => $item['description'],
                'is_available' => $item['is_available'],
                'user_id' => $owner->id,
            ]);

            // Create features
            $property->features()->create($item['features']);
            // Add images (1 main + 3 interiors)
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

        $this->command->info('5 default properties created successfully!');
    }
}
