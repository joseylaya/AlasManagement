<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table): void {
            // A null user_id plus target_roles makes this one shared notification
            // visible to every active user in one of the listed roles.
            $table->json('target_roles')->nullable();
            $table->string('event_key')->nullable()->unique();
        });

        Schema::create('notification_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();
            $table->unique(['notification_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');

        Schema::table('notifications', function (Blueprint $table): void {
            $table->dropUnique(['event_key']);
            $table->dropColumn(['target_roles', 'event_key']);
        });
    }
};
