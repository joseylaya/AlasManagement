<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ai_settings')->update([
            'max_output_tokens' => 250,
            'max_knowledge_results' => 3,
            'max_recent_messages' => 6,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('ai_settings')->update([
            'max_output_tokens' => 500,
            'max_knowledge_results' => 5,
            'max_recent_messages' => 20,
            'updated_at' => now(),
        ]);
    }
};
