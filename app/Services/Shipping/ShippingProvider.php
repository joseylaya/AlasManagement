<?php

namespace App\Services\Shipping;

interface ShippingProvider
{
    public function provider(): string;

    public function getQuote(array $input): array;
}
