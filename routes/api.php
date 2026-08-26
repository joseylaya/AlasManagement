<?php

use App\Http\Controllers\Api\PayMongoWebhookController;
use App\Http\Controllers\Api\StorefrontCatalogController;
use App\Http\Controllers\Api\StorefrontCheckoutController;
use App\Http\Controllers\Api\StorefrontShippingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SupportConversationController;

Route::prefix('storefront')->group(function () {
    Route::get('/products', [StorefrontCatalogController::class, 'index'])->name('api.storefront.products.index');
    Route::get('/products/{slug}', [StorefrontCatalogController::class, 'show'])->name('api.storefront.products.show');
});

Route::prefix('v1/storefront')->group(function () {
    Route::get('/products', [StorefrontCatalogController::class, 'index']);
    Route::get('/products/{slug}', [StorefrontCatalogController::class, 'show']);
    Route::post('/checkouts', [StorefrontCheckoutController::class, 'store'])->middleware('throttle:30,1');
    Route::post('/shipping/quotes', [StorefrontShippingController::class, 'quotes'])->middleware('throttle:30,1');
    Route::get('/orders/{publicToken}', [StorefrontCheckoutController::class, 'show'])->middleware('throttle:60,1');
    Route::post('/orders/{publicToken}/refresh-payment', [StorefrontCheckoutController::class, 'refreshPayment'])->middleware('throttle:12,1');
    Route::post('/orders/{publicToken}/regenerate-qr', [StorefrontCheckoutController::class, 'regenerateQr'])->middleware('throttle:4,1');
});

Route::post('/v1/webhooks/paymongo', PayMongoWebhookController::class)->middleware('throttle:120,1');
Route::prefix('v1/support')->middleware('throttle:30,1')->group(function () {
    Route::post('/conversations', [SupportConversationController::class, 'store']);
    Route::get('/conversations/{conversation}', [SupportConversationController::class, 'show']);
    Route::get('/conversations/{conversation}/messages', [SupportConversationController::class, 'messages']);
    Route::post('/conversations/{conversation}/messages', [SupportConversationController::class, 'send']);
});
