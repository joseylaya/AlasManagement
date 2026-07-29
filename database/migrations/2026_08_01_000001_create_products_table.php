<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name');
            $table->string('sku')->unique()->index();
            $table->string('category')->default('Uncategorized')->index();
            $table->string('color')->nullable();
            $table->string('size')->nullable();
            $table->text('description')->nullable();
            $table->decimal('selling_price', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->string('image_url')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
