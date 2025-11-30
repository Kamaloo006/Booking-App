<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookingRequest extends FormRequest
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
            'start_date' =>'nullable|date|after_or_equal:today',
            'end_date' =>'nullable|date|after:start_date',
            'payment_status' =>'nullable|in:pending,paid,failed,refunded,cancelled'
        ];
    }
}
