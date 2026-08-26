<?php

namespace App\Services\Shipping;

use App\Models\DeliveryProviderSetting;

class JntShippingProvider implements ShippingProvider
{
    public function provider(): string
    {
        return 'jnt';
    }

    public function getQuote(array $input): array
    {
        $settings = DeliveryProviderSetting::where('provider', 'jnt')->first();
        $country = mb_strtolower(trim($input['destination']['country'] ?? ''));
        if (! $settings?->enabled || ! in_array($country, ['philippines', 'ph'], true)) {
            return $this->unavailable('J&T is available for supported Philippine addresses only.');
        }
        if ($settings->mode !== 'configured_rate') {
            return $this->unavailable('J&T live quotations are not configured yet.');
        }
        $weightKg = max(0.001, (float) $input['parcel']['weight_kg']);
        $fee = max((float) $settings->minimum_fee, (float) $settings->base_fee + max(0, ceil($weightKg) - 1) * (float) $settings->additional_fee_per_kg);

        return ['provider' => 'jnt', 'service_name' => 'J&T Express', 'available' => true, 'fee' => round($fee, 2), 'currency' => 'PHP', 'quote_source' => 'configured_rate', 'estimated_delivery' => $settings->estimated_delivery];
    }

    private function unavailable(string $reason): array
    {
        return ['provider' => 'jnt', 'service_name' => 'J&T Express', 'available' => false, 'fee' => null, 'currency' => 'PHP', 'quote_source' => 'configured_rate', 'reason_unavailable' => $reason];
    }
}
