<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_announcements', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_role')->default('all');
            $table->string('title', 120);
            $table->text('message');
            $table->time('send_time')->default('20:00:00');
            $table->string('timezone')->default('Asia/Manila');
            $table->boolean('is_active')->default(true);
            $table->date('last_sent_on')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'send_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_announcements');
    }
};
