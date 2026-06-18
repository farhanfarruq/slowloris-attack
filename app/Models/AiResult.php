<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiResult extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'supporting_indicators' => 'array',
        'missing_evidence' => 'array',
        'raw_request' => 'array',
        'raw_response' => 'array',
        'logic_gate_reasons' => 'array',
        'ai_chart_data' => 'array',
        'comparison_summary' => 'array',
        'is_simulated' => 'boolean',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }
}
