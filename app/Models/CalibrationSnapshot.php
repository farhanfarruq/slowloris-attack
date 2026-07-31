<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalibrationSnapshot extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'detected_threshold' => 'float',
        'suspicious_threshold' => 'float',
        'metrics' => 'array',
        'changed_rows' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
