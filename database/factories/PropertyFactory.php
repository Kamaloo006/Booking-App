<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition()
    {
        $categories = [
            'house',
            'villa',
            'apartment'
        ];

        $category = $this->faker->randomElement($categories);

        $titles = [
            'Modern Apartment',
            'Cozy Apartment',
            'Family House',
            'Luxury Villa',
            'Newly Renovated Apartment',
            'Country-style House',
            'Elegant Villa',
            'Daily Rental Apartment'
        ];

        $descriptions = [
            'A spacious and well-designed property located close to essential services such as schools, shops, and public transport.',
            'Modern apartment fully furnished and located in a safe and quiet neighborhood, suitable for families and individuals.',
            'Large family house with spacious rooms and a private outdoor garden, perfect for those seeking comfort and privacy.',
            'High-end villa featuring a modern design, private swimming pool, and beautiful outdoor area, ideal for luxurious living.',
            'Apartment with a beautiful view and excellent finishing, located near restaurants and commercial areas.',
            'Countryside home surrounded by nature and fresh air, perfect for relaxation and peaceful living.'
        ];

        return [
            'name'         => $this->faker->randomElement($titles),
            'description'   => $this->faker->randomElement($descriptions),
            'category'      => $category,

            'governorate'   => $this->faker->randomElement([
                'Damascus',
                'Aleppo',
                'Homs',
                'Hama',
                'Latakia',
                'Tartous',
                'Daraa'
            ]),

            'city'          => $this->faker->randomElement([
                'Mazzeh',
                'Baramkeh',
                'Jaramana',
                'Sahnaya',
                'Azizia',
                'Artoz',
                'Hamma',
                'Dwelaa',
                'Baghdad Street',
                'Muhajreen',
                'Salheiya'
            ]),

            'price_per_day' => $this->faker->numberBetween(20, 200),
            'is_available'  => true,

            'user_id'       => User::where('role', 'owner')->inRandomOrder()->value('id'),
        ];
    }
}
