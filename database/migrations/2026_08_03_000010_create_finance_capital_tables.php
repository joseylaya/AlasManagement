<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->string('type')->default('cash');
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        DB::table('financial_accounts')->insert(array_map(fn ($name) => ['name'=>$name,'type'=>'cash','is_active'=>true,'created_at'=>now(),'updated_at'=>now()], ['Cash on Hand','GCash','Business Bank Account','Other Business Account']));
        Schema::create('owner_capital_injections', function (Blueprint $table) {
            $table->id(); $table->string('capital_injection_number')->unique(); $table->uuid('client_uuid')->unique();
            $table->foreignId('owner_user_id')->constrained('users'); $table->decimal('amount', 15, 2);
            $table->foreignId('financial_account_id')->constrained('financial_accounts');
            $table->string('funding_source'); $table->date('contribution_date'); $table->string('reference_number')->nullable()->unique();
            $table->text('description')->nullable(); $table->text('remarks')->nullable(); $table->string('proof_path')->nullable();
            $table->string('status')->default('posted'); $table->foreignId('cash_transaction_id')->nullable()->constrained('cash_transactions');
            $table->foreignId('created_by')->nullable()->constrained('users'); $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('reversed_by')->nullable()->constrained('users'); $table->timestamp('reversed_at')->nullable(); $table->text('reversal_reason')->nullable(); $table->timestamps();
        });
        Schema::create('finance_ledger_entries', function (Blueprint $table) {
            $table->id(); $table->foreignId('capital_injection_id')->constrained('owner_capital_injections');
            $table->string('account'); $table->enum('entry_type', ['debit','credit']); $table->decimal('amount', 15, 2); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('finance_ledger_entries'); Schema::dropIfExists('owner_capital_injections'); Schema::dropIfExists('financial_accounts'); }
};
