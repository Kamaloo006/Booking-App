<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',

            'profile_img'  => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
            'id_img'       => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',

            'date_of_birth' => 'required|date|before:today',
            'role' => 'required|in:owner,renter',

            'phone_number' => 'required|string|unique:users,phone_number|regex:/^[0-9]+$/|min:8|max:20',
        ];
    }
}
