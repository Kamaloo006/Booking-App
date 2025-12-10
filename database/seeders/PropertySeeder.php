<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Property;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */


    public function run(): void
    {
        // تحقق من وجود الملفات
        if (!Storage::disk('public')->exists('demo/properties_main_imgs')) {
            $this->command->warn('Demo images directory not found!');
            // استمر في الإنشاء بدون صور
        }

        $mainImages = collect(Storage::disk('public')->files('demo/properties_main_imgs'));
        $interiorImages = collect(Storage::disk('public')->files('demo/properties_interior_imgs'));

        Property::factory()->count(8)->create()->each(function ($property) use ($mainImages, $interiorImages) {

            $property->features()->create([
                'rooms' => rand(1, 5),
                'bathrooms' => rand(1, 3),
                'kitchens' => rand(1, 2),
                'area' => rand(60, 200),
            ]);

            if (!$mainImages->isEmpty()) {
                $main = $mainImages->random();
                $property->images()->create([
                    'image_path' => $main,
                    'is_main'    => true
                ]);
            }

            // 3) الصور الداخلية
            if (!$interiorImages->isEmpty()) {
                $interiorCount = rand(2, 4);
                $maxCount = min($interiorCount, $interiorImages->count());

                if ($maxCount > 0) {
                    $selectedInteriors = $interiorImages->random($maxCount);

                    foreach ($selectedInteriors as $img) {
                        $property->images()->create([
                            'image_path' => $img,
                            'is_main'    => false
                        ]);
                    }
                }
            }
        });
    }
}
