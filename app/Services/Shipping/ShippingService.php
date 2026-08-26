<?php

namespace App\Services\Shipping;

use App\Models\DeliveryProviderSetting;
use App\Models\Product;
use App\Models\ShippingQuote;
use Illuminate\Validation\ValidationException;

class ShippingService
{
    public function __construct(private JntShippingProvider $jnt, private MaximShippingProvider $maxim) {}

    public function quote(array $address, array $items, string $sessionId): array
    {
        [$products, $parcel, $cartHash] = $this->parcel($items);
        $address = $this->normalizeAddress($address);
        $destinationHash = hash('sha256', json_encode($address));
        $input = ['origin' => null, 'destination' => $address, 'parcel' => $parcel, 'cart_items' => $items];

        return collect([$this->jnt, $this->maxim])->map(function (ShippingProvider $provider) use ($input, $sessionId, $destinationHash, $cartHash, $address, $parcel) {
            $result = $provider->getQuote($input);
            if (! $result['available']) {
                return $result;
            }
            $ttl = DeliveryProviderSetting::where('provider', $provider->provider())->value('quote_ttl_minutes') ?: 20;
            $existing = ShippingQuote::query()->where('session_id', $sessionId)->where('provider', $provider->provider())->where('destination_hash', $destinationHash)->where('cart_hash', $cartHash)->where('expires_at', '>', now())->latest()->first();
            $quote = $existing ?: ShippingQuote::create([
                'session_id' => $sessionId, 'provider' => $provider->provider(), 'service_name' => $result['service_name'],
                'destination_hash' => $destinationHash, 'cart_hash' => $cartHash, 'amount' => $result['fee'], 'currency' => 'PHP',
                'source' => $result['quote_source'], 'destination_snapshot' => $address, 'parcel_snapshot' => $parcel, 'expires_at' => now()->addMinutes($ttl),
            ]);

            return [...$result, 'fee' => (float) $quote->amount, 'quote_id' => $quote->id, 'expires_at' => $quote->expires_at->toIso8601String()];
        })->values()->all();
    }

    public function validateQuote(string $quoteId, string $sessionId, array $address, array $items): ShippingQuote
    {
        $quote = ShippingQuote::whereKey($quoteId)->where('session_id', $sessionId)->first();
        if (! $quote || $quote->expires_at->isPast()) {
            throw ValidationException::withMessages(['shipping_quote_id' => ['Your delivery quote expired. Please calculate delivery again.']]);
        }
        [, $parcel, $cartHash] = $this->parcel($items);
        $address = $this->normalizeAddress($address);
        if (! hash_equals($quote->cart_hash, $cartHash) || ! hash_equals($quote->destination_hash, hash('sha256', json_encode($address)))) {
            throw ValidationException::withMessages(['shipping_quote_id' => ['Your cart or address changed. Please review the updated delivery fee.']]);
        }
        $provider = $quote->provider === 'maxim' ? $this->maxim : $this->jnt;
        $fresh = $provider->getQuote(['origin' => null, 'destination' => $address, 'parcel' => $parcel, 'cart_items' => $items]);
        if (! $fresh || ! $fresh['available'] || abs((float) $fresh['fee'] - (float) $quote->amount) > 0.001) {
            throw ValidationException::withMessages(['shipping_quote_id' => ['Your delivery fee changed. Please review the updated total.']]);
        }

        return $quote;
    }

    private function parcel(array $items): array
    {
        $requested = collect($items)->keyBy('variant_id')->sortKeys();
        $products = Product::whereIn('id', $requested->keys())->get()->keyBy('id');
        if ($products->count() !== $requested->count()) {
            throw ValidationException::withMessages(['items' => ['One or more variants are unavailable.']]);
        }
        $defaultWeight = (int) (DeliveryProviderSetting::where('provider', 'jnt')->value('default_weight_grams') ?: 500);
        $weight = $requested->map(fn ($line, $id) => (int) ($products[$id]->weight_grams ?: $defaultWeight) * (int) $line['quantity'])->sum();
        $normalizedItems = $requested->map(fn ($line, $id) => ['variant_id' => (int) $id, 'quantity' => (int) $line['quantity']])->values()->all();
        $parcel = ['weight_kg' => $weight / 1000, 'weight_grams' => $weight];

        return [$products, $parcel, hash('sha256', json_encode($normalizedItems))];
    }

    private function normalizeAddress(array $address): array
    {
        return collect($address)->map(fn ($value) => is_string($value) ? trim(preg_replace('/\s+/', ' ', $value)) : $value)->sortKeys()->all();
    }
}
