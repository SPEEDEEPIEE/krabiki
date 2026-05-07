<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'venue_id' => 'required|exists:venues,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
            'max_participants' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0',
        ];
    }
}