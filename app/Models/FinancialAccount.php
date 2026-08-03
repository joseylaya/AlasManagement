<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinancialAccount extends Model { protected $fillable=['name','type','is_active']; protected $casts=['is_active'=>'boolean']; }
