<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SupportCustomer extends Model
{
    use HasUuids;

    protected $fillable = ['user_id', 'visitor_id', 'access_token_hash', 'display_name', 'email', 'phone'];

    protected $hidden = ['access_token_hash'];

    public function conversations() { return $this->hasMany(SupportConversation::class, 'customer_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
