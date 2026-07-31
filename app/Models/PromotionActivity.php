<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionActivity extends Model
{
    protected $fillable = [
        'user_id',
        'activity_type',
        'activity_date',
        'campaign',
        'platform',
        'outcome',
        'proof_path',
        'proof_original_name',
        'proof_size',
        'proof_status',
        'status',
        'approved_amount',
        'review_notes',
        'reviewed_at',
        'reviewed_by',
        'proof_purged_at',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'approved_amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'proof_purged_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function compensationRecord()
    {
        return $this->hasOne(CompensationRecord::class);
    }
}
