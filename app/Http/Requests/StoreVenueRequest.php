<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'capacity' => 'required|integer|min:1',
            'hourly_fee' => 'nullable|numeric|min:0',
        ];
    }
}