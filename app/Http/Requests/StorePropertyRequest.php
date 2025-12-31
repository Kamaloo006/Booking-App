<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
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

            'city' => 'required|string',
            'governorate' => 'required|string',
            'price_per_day' => 'required|numeric',
            'description' => 'required|string',
            'name' => 'required|string|max:255',
            'images' => 'required|array|min:3|max:9',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:10024',
            'is_available' => 'nullable|boolean',
            'category' => 'required|in:house,villa,apartment',
            'area' => 'required|integer|min:50',
            'bathrooms' => 'required|integer|min:1',
            'kitchens' => 'required|integer|min:1',
            'rooms' => 'required|integer|min:1'
        ];
    }
}
