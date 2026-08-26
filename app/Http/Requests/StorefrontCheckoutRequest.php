<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorefrontCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:40'],
            'delivery_method' => ['required', 'in:shipping,meetup'],
            'shipping_address' => ['required_if:delivery_method,shipping', 'nullable', 'string', 'max:2000'],
            'delivery_address' => ['required_if:delivery_method,shipping', 'nullable', 'array'],
            'delivery_address.country' => ['required_if:delivery_method,shipping', 'string', 'max:80'],
            'delivery_address.region' => ['nullable', 'string', 'max:120'],
            'delivery_address.region_code' => ['required_if:delivery_method,shipping', 'digits:10'],
            'delivery_address.province' => ['required_if:delivery_method,shipping', 'string', 'max:120'],
            'delivery_address.city' => ['required_if:delivery_method,shipping', 'string', 'max:120'],
            'delivery_address.city_code' => ['required_if:delivery_method,shipping', 'digits:10'],
            'delivery_address.municipality' => ['nullable', 'string', 'max:120'],
            'delivery_address.barangay' => ['required_if:delivery_method,shipping', 'string', 'max:120'],
            'delivery_address.barangay_code' => ['required_if:delivery_method,shipping', 'digits:10'],
            'delivery_address.postal_code' => ['required_if:delivery_method,shipping', 'string', 'max:12'],
            'delivery_address.street_address' => ['required_if:delivery_method,shipping', 'string', 'max:500'],
            'delivery_address.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'delivery_address.longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'shipping_quote_id' => ['required_if:delivery_method,shipping', 'nullable', 'uuid'],
            'shipping_session_id' => ['required_if:delivery_method,shipping', 'nullable', 'uuid'],
            'meetup_location' => ['required_if:delivery_method,meetup', 'nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.variant_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
