<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OwnerCapitalInjection extends Model {
    protected $fillable=['capital_injection_number','client_uuid','owner_user_id','amount','financial_account_id','funding_source','contribution_date','reference_number','description','remarks','proof_path','status','cash_transaction_id','created_by','updated_by','reversed_by','reversed_at','reversal_reason'];
    protected $casts=['amount'=>'decimal:2','contribution_date'=>'date','reversed_at'=>'datetime'];
    public function account(){return $this->belongsTo(FinancialAccount::class,'financial_account_id');}
    public function cashTransaction(){return $this->belongsTo(CashTransaction::class);}
    public function owner(){return $this->belongsTo(User::class,'owner_user_id');}
}
