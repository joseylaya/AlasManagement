<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('salary_profiles', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('salary_amount', 15, 2); $table->string('frequency'); $table->date('effective_date');
            $table->string('payment_method')->default('cash'); $table->string('status')->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
            $table->unique(['user_id', 'status']);
        });
        Schema::create('compensation_records', function (Blueprint $table) {
            $table->id(); $table->string('record_number')->unique(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_profile_id')->nullable()->constrained()->nullOnDelete(); $table->string('type');
            $table->decimal('amount', 15, 2); $table->date('period_start')->nullable(); $table->date('period_end')->nullable();
            $table->string('status')->default('draft'); $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable(); $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_to_finance_at')->nullable(); $table->timestamp('paid_at')->nullable(); $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cash_transaction_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('compensation_records'); Schema::dropIfExists('salary_profiles'); }
};
