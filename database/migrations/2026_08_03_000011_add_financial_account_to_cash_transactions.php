<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up(): void { Schema::table('cash_transactions', function(Blueprint $table){ $table->foreignId('financial_account_id')->nullable()->after('amount')->constrained('financial_accounts')->nullOnDelete(); }); } public function down(): void { Schema::table('cash_transactions', fn(Blueprint $table)=>$table->dropConstrainedForeignId('financial_account_id')); } };
