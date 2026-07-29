<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('delivery_method')->default('shipping'); // shipping, meetup
            $table->text('shipping_address')->nullable();
            $table->date('meetup_date')->nullable();
            $table->string('meetup_location')->nullable();
            $table->string('order_status')->default('pending')->index(); // pending, confirmed, packed, shipped, completed, cancelled
            $table->string('payment_status')->default('pending')->index(); // pending, partial, paid, refunded
            $table->string('payment_method')->default('cash');
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
