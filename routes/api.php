<?php

use App\Http\Controllers\Api\StorefrontCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('storefront')->group(function () {
    Route::get('/products', [StorefrontCatalogController::class, 'index'])->name('api.storefront.products.index');
    Route::get('/products/{slug}', [StorefrontCatalogController::class, 'show'])->name('api.storefront.products.show');
});
