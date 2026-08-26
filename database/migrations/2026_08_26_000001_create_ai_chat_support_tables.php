<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(true);
            $table->string('provider')->default('gemini');
            $table->string('model')->default('gemini-3.7-flash');
            $table->string('embedding_model')->default('gemini-embedding-2');
            $table->unsignedSmallInteger('max_output_tokens')->default(500);
            $table->unsignedSmallInteger('max_knowledge_results')->default(5);
            $table->unsignedSmallInteger('max_recent_messages')->default(20);
            $table->unsignedSmallInteger('provider_timeout_seconds')->default(20);
            $table->text('welcome_message')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('support_customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('visitor_id')->nullable()->unique();
            $table->string('access_token_hash', 64)->nullable();
            $table->string('display_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->timestamps();
            $table->index('user_id');
        });

        Schema::create('support_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('support_customers')->restrictOnDelete();
            $table->string('channel', 30)->default('WEBSITE');
            $table->string('mode', 30)->default('AI_ACTIVE');
            $table->string('status', 30)->default('OPEN');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('customer_unread_count')->default(0);
            $table->unsignedInteger('admin_unread_count')->default(0);
            $table->json('context')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_customer_message_at')->nullable();
            $table->timestamp('last_admin_message_at')->nullable();
            $table->timestamp('last_ai_message_at')->nullable();
            $table->timestamp('taken_over_at')->nullable();
            $table->timestamp('ai_resumed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'last_message_at']);
            $table->index(['mode', 'last_message_at']);
            $table->index(['customer_id', 'last_message_at']);
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->string('sender_type', 20);
            $table->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('content_type', 20)->default('TEXT');
            $table->text('content');
            $table->boolean('is_ai_generated')->default(false);
            $table->string('client_message_id', 100)->nullable();
            $table->string('external_message_id')->nullable();
            // Add the self-referencing constraint after the table exists. PostgreSQL
            // cannot resolve this reference while the table's primary key is still
            // being created.
            $table->uuid('reply_to_message_id')->nullable();
            $table->string('delivery_status', 20)->default('SENT');
            $table->timestamps();
            $table->timestamp('edited_at')->nullable();
            $table->unique(['conversation_id', 'client_message_id']);
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::table('support_messages', function (Blueprint $table) {
            $table->foreign('reply_to_message_id')
                ->references('id')
                ->on('support_messages')
                ->nullOnDelete();
        });

        Schema::create('support_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->foreignId('admin_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'ended_at']);
        });

        Schema::create('support_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->string('event_type', 50);
            $table->string('actor_type', 20);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('ai_knowledge_bases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('knowledge_base_id')->constrained('ai_knowledge_bases')->restrictOnDelete();
            $table->uuid('previous_version_id')->nullable();
            $table->string('title');
            $table->longText('content');
            $table->string('source_type', 30)->default('MANUAL');
            $table->string('category')->nullable();
            $table->string('status', 20)->default('DRAFT');
            $table->unsignedInteger('version')->default(1);
            $table->string('embedding_provider')->nullable();
            $table->string('embedding_model')->nullable();
            $table->text('index_error')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
        });

        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('ai_knowledge_documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->text('content');
            $table->json('metadata')->nullable();
            $table->json('embedding')->nullable();
            $table->string('embedding_model');
            $table->timestamps();
            $table->unique(['document_id', 'chunk_index']);
        });
        if (DB::getDriverName() === 'pgsql') {
            $dimension = max(1, (int) config('services.ai.embedding_dimension', 1536));
            DB::statement("ALTER TABLE ai_knowledge_chunks ALTER COLUMN embedding TYPE vector({$dimension}) USING NULL");
            DB::statement('CREATE INDEX ai_knowledge_chunks_embedding_idx ON ai_knowledge_chunks USING hnsw (embedding vector_cosine_ops)');
        }

        Schema::create('ai_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('support_conversations')->cascadeOnDelete();
            $table->foreignUuid('trigger_message_id')->constrained('support_messages')->cascadeOnDelete();
            $table->string('provider', 40);
            $table->string('model');
            $table->string('mode', 30);
            $table->string('status', 30)->default('PROCESSING');
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique('trigger_message_id');
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('ai_run_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('ai_run_id')->constrained('ai_runs')->cascadeOnDelete();
            $table->string('source_type', 30);
            $table->string('source_id');
            $table->decimal('similarity_score', 8, 6)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        DB::table('ai_settings')->insert([
            'enabled' => true,
            'provider' => 'gemini',
            'model' => 'gemini-3.7-flash',
            'embedding_model' => 'gemini-embedding-2',
            'welcome_message' => 'Hi! How can ALAS Support help you today?',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE support_customers ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE support_conversations ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE support_messages ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE ai_knowledge_documents ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE ai_knowledge_chunks ENABLE ROW LEVEL SECURITY');
            DB::statement("DO $$ BEGIN IF EXISTS (SELECT 1 FROM pg_publication WHERE pubname = 'supabase_realtime') THEN ALTER PUBLICATION supabase_realtime ADD TABLE support_conversations, support_messages; END IF; EXCEPTION WHEN duplicate_object THEN NULL; END $$");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_run_sources');
        Schema::dropIfExists('ai_runs');
        Schema::dropIfExists('ai_knowledge_chunks');
        Schema::dropIfExists('ai_knowledge_documents');
        Schema::dropIfExists('ai_knowledge_bases');
        Schema::dropIfExists('support_events');
        Schema::dropIfExists('support_assignments');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_conversations');
        Schema::dropIfExists('support_customers');
        Schema::dropIfExists('ai_settings');
    }
};
