<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcquisitionFile extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'top_source_ips' => 'array',
        'top_destination_ips' => 'array',
        'protocol_distribution' => 'array',
        'parsed_summary' => 'array',
        'capture_started_at' => 'datetime',
        'capture_ended_at' => 'datetime',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function validationFiles(): HasMany
    {
        return $this->hasMany(ValidationFile::class);
    }
}
