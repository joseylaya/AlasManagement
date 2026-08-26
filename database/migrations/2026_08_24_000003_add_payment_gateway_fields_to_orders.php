<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('commerce_mode', 20)->default('live')->index();
            $table->string('paymongo_checkout_session_id')->nullable()->unique();
            $table->text('paymongo_checkout_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['paymongo_checkout_session_id']);
            $table->dropIndex(['commerce_mode']);
            $table->dropColumn(['commerce_mode', 'paymongo_checkout_session_id', 'paymongo_checkout_url']);
        });
    }
};
