<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorefrontShippingQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'uuid'],
            'address' => ['required', 'array'],
            'address.country' => ['required', 'string', 'max:80'],
            'address.region' => ['nullable', 'string', 'max:120'],
            'address.region_code' => ['required', 'digits:10'],
            'address.province' => ['required', 'string', 'max:120'],
            'address.city' => ['required', 'string', 'max:120'],
            'address.city_code' => ['required', 'digits:10'],
            'address.municipality' => ['nullable', 'string', 'max:120'],
            'address.barangay' => ['required', 'string', 'max:120'],
            'address.barangay_code' => ['required', 'digits:10'],
            'address.postal_code' => ['required', 'string', 'max:12'],
            'address.street_address' => ['required', 'string', 'max:500'],
            'address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'address.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.variant_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
