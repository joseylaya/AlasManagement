<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_drawals', function (Blueprint $table) {
            $table->id();
            $table->string('drawal_number')->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('amount', 15, 2);
            $table->date('drawal_date')->index();
            $table->text('reason')->nullable();
            $table->string('status')->default('completed');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_drawals');
    }
};
