<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storefront_products', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('material')->nullable();
            $table->string('status')->default('active')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('storefront_product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storefront_product_id')->constrained('storefront_products')->cascadeOnDelete();
            $table->string('image_url');
            $table->string('alt_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('status')->default('active')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('storefront_product_id')->nullable()->after('id')->constrained('storefront_products')->nullOnDelete();
        });

        DB::table('products')->whereNull('deleted_at')->orderBy('id')->each(function ($product) {
            $baseSlug = Str::slug($product->product_name) ?: 'product';
            $slug = $baseSlug.'-'.$product->id;
            $now = now();
            $storefrontProductId = DB::table('storefront_products')->insertGetId([
                'slug' => $slug,
                'name' => $product->product_name,
                'description' => $product->description,
                'status' => $product->status === 'active' ? 'active' : 'inactive',
                'created_by' => $product->created_by,
                'updated_by' => $product->updated_by,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('products')->where('id', $product->id)->update(['storefront_product_id' => $storefrontProductId]);

            if ($product->image_url) {
                DB::table('storefront_product_images')->insert([
                    'storefront_product_id' => $storefrontProductId,
                    'image_url' => $product->image_url,
                    'alt_text' => $product->product_name,
                    'created_by' => $product->created_by,
                    'updated_by' => $product->updated_by,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('storefront_product_id');
        });
        Schema::dropIfExists('storefront_product_images');
        Schema::dropIfExists('storefront_products');
    }
};
