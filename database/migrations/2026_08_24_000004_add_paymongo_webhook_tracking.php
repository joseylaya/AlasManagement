<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('paymongo_payment_intent_id')->nullable()->unique();
            $table->string('paymongo_payment_id')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
        });
        Schema::create('paymongo_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event_type');
            $table->boolean('livemode');
            $table->string('status')->default('received');
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paymongo_webhook_events');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['paymongo_payment_intent_id']);
            $table->dropUnique(['paymongo_payment_id']);
            $table->dropColumn(['paymongo_payment_intent_id', 'paymongo_payment_id', 'paid_at']);
        });
    }
};
