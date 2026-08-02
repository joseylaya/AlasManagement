<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardBanner extends Model
{
    protected $fillable = [
        'uploaded_by', 'title', 'image_path', 'image_original_name', 'is_active', 'display_order',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
