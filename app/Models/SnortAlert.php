<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnortAlert extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'alert_timestamp' => 'datetime',
        'raw' => 'array',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function validationFile(): BelongsTo
    {
        return $this->belongsTo(ValidationFile::class);
    }
}
