<?php

namespace App\Services\Shipping;

use App\Models\DeliveryProviderSetting;
use App\Models\DeliveryServiceArea;

class MaximShippingProvider implements ShippingProvider
{
    public function provider(): string
    {
        return 'maxim';
    }

    public function getQuote(array $input): array
    {
        $settings = DeliveryProviderSetting::where('provider', 'maxim')->first();
        if (! $settings?->enabled) {
            return $this->unavailable('Maxim Delivery is currently unavailable.');
        }

        $destination = $input['destination'];
        $area = $this->serviceArea($destination);
        if (! $area) {
            return $this->unavailable('Available for selected Cebu areas only.');
        }
        if ($settings->mode !== 'configured_rate') {
            return $this->unavailable('Maxim live quotations are not configured yet.');
        }

        $latitude = isset($destination['latitude']) ? (float) $destination['latitude'] : (float) $area->latitude;
        $longitude = isset($destination['longitude']) ? (float) $destination['longitude'] : (float) $area->longitude;
        if (! $settings->origin_latitude || ! $settings->origin_longitude || ! $latitude || ! $longitude) {
            return $this->unavailable('A complete serviceable address is needed for a Maxim estimate.');
        }
        $distance = $this->distanceKm((float) $settings->origin_latitude, (float) $settings->origin_longitude, $latitude, $longitude);
        if ($settings->maximum_distance_km !== null && $distance > (float) $settings->maximum_distance_km) {
            return $this->unavailable('This address is beyond the configured Maxim delivery distance.');
        }
        $chargeableKm = max(0, $distance - (float) $settings->base_distance_km);
        $fee = max((float) $settings->minimum_fee, (float) $settings->base_fee + ceil($chargeableKm) * (float) $settings->additional_fee_per_km);

        return ['provider' => 'maxim', 'service_name' => 'Maxim Delivery', 'available' => true, 'fee' => round($fee, 2), 'currency' => 'PHP', 'quote_source' => 'configured_rate', 'estimated_delivery' => $settings->estimated_delivery, 'metadata' => ['estimated_distance_km' => round($distance, 2)]];
    }

    private function serviceArea(array $address): ?DeliveryServiceArea
    {
        $normalize = fn ($value) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
        $locality = fn ($value) => trim(preg_replace('/(^city of | city$)/', '', $normalize($value)));
        $city = $locality($address['city'] ?? $address['municipality'] ?? '');

        return DeliveryServiceArea::query()->where('provider', 'maxim')->where('enabled', true)->get()->first(fn ($area) => $normalize($area->country) === $normalize($address['country'] ?? '') &&
            $normalize($area->province) === $normalize($address['province'] ?? '') &&
            $city === $locality($area->city)
        );
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function unavailable(string $reason): array
    {
        return ['provider' => 'maxim', 'service_name' => 'Maxim Delivery', 'available' => false, 'fee' => null, 'currency' => 'PHP', 'quote_source' => 'configured_rate', 'reason_unavailable' => $reason];
    }
}
