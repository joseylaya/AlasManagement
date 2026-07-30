<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CompensationRecord extends Model { protected $fillable=['record_number','user_id','salary_profile_id','type','amount','period_start','period_end','status','remarks','approved_at','approved_by','posted_to_finance_at','paid_at','paid_by','cash_transaction_id','created_by','updated_by']; protected $casts=['amount'=>'decimal:2','period_start'=>'date','period_end'=>'date','approved_at'=>'datetime','posted_to_finance_at'=>'datetime','paid_at'=>'datetime']; public function user(){return $this->belongsTo(User::class);} public function cashTransaction(){return $this->belongsTo(CashTransaction::class);} }
