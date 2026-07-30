<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SalaryProfile extends Model { protected $fillable=['user_id','salary_amount','frequency','effective_date','payment_method','status','created_by','updated_by']; protected $casts=['salary_amount'=>'decimal:2','effective_date'=>'date']; public function user(){return $this->belongsTo(User::class);} }
