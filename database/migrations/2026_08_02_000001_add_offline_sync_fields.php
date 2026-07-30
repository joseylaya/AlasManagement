<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('order_number');
            $table->unsignedInteger('record_version')->default(1)->after('approval_status');
            $table->timestamp('server_updated_at')->nullable()->after('record_version');
            $table->string('sync_source')->default('online')->after('server_updated_at');
        });
        Schema::table('owner_drawals', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('drawal_number');
            $table->string('payment_source')->nullable()->after('reason');
            $table->text('remarks')->nullable()->after('payment_source');
            $table->unsignedInteger('record_version')->default(1)->after('status');
            $table->timestamp('server_updated_at')->nullable()->after('record_version');
            $table->string('sync_source')->default('online')->after('server_updated_at');
        });
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->uuid('client_uuid')->nullable()->unique()->after('transaction_number');
            $table->string('direction')->nullable()->after('type');
            $table->string('sync_source')->default('online')->after('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', fn (Blueprint $table) => $table->dropColumn(['client_uuid', 'direction', 'sync_source']));
        Schema::table('owner_drawals', fn (Blueprint $table) => $table->dropColumn(['client_uuid', 'payment_source', 'remarks', 'record_version', 'server_updated_at', 'sync_source']));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['client_uuid', 'record_version', 'server_updated_at', 'sync_source']));
    }
};
