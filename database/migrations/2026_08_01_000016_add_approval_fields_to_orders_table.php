<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Approval workflow fields
            $table->string('approval_status')
                  ->default('pending_approval')
                  ->index()
                  ->after('order_status');
            // Values: pending_approval | approved | rejected

            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null')
                  ->after('approval_status');

            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('approved_by');

            $table->text('rejection_reason')
                  ->nullable()
                  ->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'approval_status',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
