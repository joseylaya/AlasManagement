<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->unique();
            $table->uuid('checkout_idempotency_key')->nullable()->unique();
            $table->string('currency', 3)->default('PHP');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropUnique(['checkout_idempotency_key']);
            $table->dropColumn(['public_token', 'checkout_idempotency_key', 'currency']);
        });
    }
};
