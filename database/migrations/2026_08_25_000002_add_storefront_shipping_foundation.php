<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('weight_grams')->nullable();
            $table->decimal('package_length_cm', 8, 2)->nullable();
            $table->decimal('package_width_cm', 8, 2)->nullable();
            $table->decimal('package_height_cm', 8, 2)->nullable();
        });

        Schema::create('delivery_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->unique();
            $table->boolean('enabled')->default(true);
            $table->string('mode', 30)->default('configured_rate');
            $table->decimal('base_fee', 10, 2)->default(0);
            $table->decimal('base_distance_km', 8, 2)->default(0);
            $table->decimal('additional_fee_per_km', 10, 2)->default(0);
            $table->decimal('additional_fee_per_kg', 10, 2)->default(0);
            $table->decimal('minimum_fee', 10, 2)->default(0);
            $table->decimal('maximum_distance_km', 8, 2)->nullable();
            $table->unsignedInteger('default_weight_grams')->default(500);
            $table->json('origin_address');
            $table->decimal('origin_latitude', 10, 7)->nullable();
            $table->decimal('origin_longitude', 10, 7)->nullable();
            $table->unsignedInteger('quote_ttl_minutes')->default(20);
            $table->string('estimated_delivery')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_service_areas', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 30)->index();
            $table->string('country', 80);
            $table->string('province', 120);
            $table->string('city', 120);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['provider', 'country', 'province', 'city'], 'delivery_area_unique');
        });

        Schema::create('shipping_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id')->index();
            $table->string('provider', 30)->index();
            $table->string('service_name');
            $table->string('destination_hash', 64);
            $table->string('cart_hash', 64);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('PHP');
            $table->string('source', 30)->default('configured_rate');
            $table->json('destination_snapshot');
            $table->json('parcel_snapshot');
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->index(['session_id', 'destination_hash', 'cart_hash'], 'shipping_quote_lookup');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('shipping_amount', 15, 2)->default(0);
            $table->string('delivery_provider', 30)->nullable()->index();
            $table->string('delivery_service')->nullable();
            $table->uuid('shipping_quote_id')->nullable()->index();
            $table->string('shipping_quote_source', 30)->nullable();
            $table->string('shipping_status', 30)->default('pending')->index();
            $table->string('tracking_number')->nullable()->index();
            $table->text('tracking_url')->nullable();
            $table->json('delivery_address_snapshot')->nullable();
        });

        $origin = json_encode([
            'country' => 'Philippines', 'province' => 'Cebu', 'city' => 'Cebu City',
            'barangay' => 'Mambaling', 'postal_code' => '6000',
            'street_address' => 'ALAS Clothing, Lynch Street, Alaska Mambaling, Candido Padilla Street',
        ]);
        $now = now();
        DB::table('delivery_provider_settings')->insert([
            ['provider' => 'jnt', 'enabled' => true, 'mode' => 'configured_rate', 'base_fee' => 120, 'base_distance_km' => 0, 'additional_fee_per_km' => 0, 'additional_fee_per_kg' => 25, 'minimum_fee' => 120, 'maximum_distance_km' => null, 'default_weight_grams' => 500, 'origin_address' => $origin, 'origin_latitude' => 10.2897916, 'origin_longitude' => 123.8830930, 'quote_ttl_minutes' => 20, 'estimated_delivery' => '3–7 business days', 'created_at' => $now, 'updated_at' => $now],
            ['provider' => 'maxim', 'enabled' => true, 'mode' => 'configured_rate', 'base_fee' => 60, 'base_distance_km' => 2, 'additional_fee_per_km' => 15, 'additional_fee_per_kg' => 0, 'minimum_fee' => 60, 'maximum_distance_km' => 35, 'default_weight_grams' => 500, 'origin_address' => $origin, 'origin_latitude' => 10.2897916, 'origin_longitude' => 123.8830930, 'quote_ttl_minutes' => 20, 'estimated_delivery' => 'Same-day local delivery', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('delivery_service_areas')->insert(collect([
            ['city' => 'Cebu City', 'latitude' => 10.3156992, 'longitude' => 123.8854366],
            ['city' => 'Mandaue City', 'latitude' => 10.3236100, 'longitude' => 123.9222200],
            ['city' => 'Lapu-Lapu City', 'latitude' => 10.3102800, 'longitude' => 123.9494400],
        ])->map(fn ($area) => [...$area, 'provider' => 'maxim', 'country' => 'Philippines', 'province' => 'Cebu', 'enabled' => true, 'created_at' => $now, 'updated_at' => $now])->all());
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal_amount', 'shipping_amount', 'delivery_provider', 'delivery_service', 'shipping_quote_id', 'shipping_quote_source', 'shipping_status', 'tracking_number', 'tracking_url', 'delivery_address_snapshot']);
        });
        Schema::dropIfExists('shipping_quotes');
        Schema::dropIfExists('delivery_service_areas');
        Schema::dropIfExists('delivery_provider_settings');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['weight_grams', 'package_length_cm', 'package_width_cm', 'package_height_cm']);
        });
    }
};
