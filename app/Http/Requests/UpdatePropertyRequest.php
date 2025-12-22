<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'city' => 'sometimes|string',
            'governorate' => 'sometimes|string',
            'price_per_day' => 'sometimes|numeric',
            'description' => 'sometimes|string',
            'is_available' => 'sometimes|boolean',
            'category' => 'sometimes|in:house,villa,apartment',
            'name' => 'sometimes|string|max:255',

            'area' => 'sometimes|integer|min:50',
            'bathrooms' => 'sometimes|integer|min:1',
            'kitchens' => 'sometimes|integer|min:1',
            'rooms' => 'sometimes|integer|min:1'

            // 'images' => 'sometimes|array|min:1',
            // 'images.*' => "image|mimes:jpeg,png,jpg,gif|max:5120",

            // // check if image exists in property_images table
            // 'main_image_id' => 'sometimes|exists:property_images,id'
        ];
    }
}
