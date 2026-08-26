<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('paymongo_payment_method_id')->nullable()->index();
            $table->text('paymongo_qr_image_url')->nullable();
            $table->timestamp('paymongo_qr_expires_at')->nullable()->index();
            $table->unsignedInteger('paymongo_payment_attempt')->default(0);
            $table->string('payment_error_code')->nullable();
        });
        Schema::table('paymongo_webhook_events', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('paymongo_webhook_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['paymongo_payment_method_id']);
            $table->dropIndex(['paymongo_qr_expires_at']);
            $table->dropColumn(['paymongo_payment_method_id', 'paymongo_qr_image_url', 'paymongo_qr_expires_at', 'paymongo_payment_attempt', 'payment_error_code']);
        });
    }
};
