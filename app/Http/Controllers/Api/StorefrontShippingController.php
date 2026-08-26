<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorefrontShippingQuoteRequest;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\JsonResponse;

class StorefrontShippingController extends Controller
{
    public function quotes(StorefrontShippingQuoteRequest $request, ShippingService $shipping): JsonResponse
    {
        $data = $request->validated();

        return response()->json(['data' => ['quotes' => $shipping->quote($data['address'], $data['items'], $data['session_id'])]]);
    }
}
