<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ai_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->string('status', 30)->default('DEBOUNCING');
            $table->unsignedInteger('priority')->default(0);
            $table->foreignUuid('first_message_id')->constrained('support_messages')->cascadeOnDelete();
            $table->foreignUuid('last_message_id')->constrained('support_messages')->cascadeOnDelete();
            $table->timestamp('batch_started_at');
            $table->timestamp('ready_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->string('model_used')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->longText('generated_content')->nullable();
            $table->boolean('escalate_after_reply')->default(false);
            $table->timestamps();
            $table->index(['conversation_id', 'status', 'created_at']);
            $table->index(['status', 'ready_at', 'priority', 'created_at']);
            $table->unique(['conversation_id', 'last_message_id']);
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE support_ai_jobs ENABLE ROW LEVEL SECURITY');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ai_jobs');
    }
};
