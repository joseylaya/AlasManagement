<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->json('payload')->nullable()->after('content');
        });
        Schema::create('support_message_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->foreignUuid('message_id')->nullable()->constrained('support_messages')->nullOnDelete();
            $table->foreignUuid('customer_id')->constrained('support_customers')->restrictOnDelete();
            $table->string('idempotency_key', 120);
            $table->string('action_type', 40);
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status', 30);
            $table->json('result_metadata')->nullable();
            $table->timestamps();
            $table->unique(['conversation_id', 'idempotency_key']);
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_message_actions');
        Schema::table('support_messages', fn (Blueprint $table) => $table->dropColumn('payload'));
    }
};
