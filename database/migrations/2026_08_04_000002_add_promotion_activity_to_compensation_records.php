<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compensation_records', function (Blueprint $table) {
            // Keep this SQLite-safe: Laravel's constrained foreign-key helper
            // rebuilds the existing table on SQLite. The unique reference still
            // guarantees that one activity can create only one payout record.
            $table->unsignedBigInteger('promotion_activity_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('compensation_records', function (Blueprint $table) {
            $table->dropUnique('compensation_records_promotion_activity_id_unique');
            $table->dropColumn('promotion_activity_id');
        });
    }
};
