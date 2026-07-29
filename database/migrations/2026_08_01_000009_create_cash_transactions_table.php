<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique()->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('type')->index(); // sale, expense, owner_withdrawal, salary, refund, adjustment
            $table->decimal('amount', 15, 2); // Positive for inflow, negative for outflow
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->onDelete('set null');
            $table->foreignId('owner_drawal_id')->nullable()->constrained('owner_drawals')->onDelete('set null');
            $table->text('description')->nullable();
            $table->dateTime('transaction_date')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
