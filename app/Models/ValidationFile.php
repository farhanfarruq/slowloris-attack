<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationFile extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'top_source_ips' => 'array',
        'top_destination_ports' => 'array',
        'alert_timeline' => 'array',
        'parsed_summary' => 'array',
        'matches_slow_http_pattern' => 'boolean',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function acquisitionFile(): BelongsTo
    {
        return $this->belongsTo(AcquisitionFile::class);
    }
}
