<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtractedFeature extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'raw_features' => 'array',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function radarScores(): array
    {
        $scores = [
            'connection_duration_score' => (float) $this->connection_duration_score,
            'header_anomaly_score' => (float) $this->header_anomaly_score,
            'low_bandwidth_high_connection_score' => (float) $this->low_bandwidth_high_connection_score,
            'snort_alert_score' => (float) $this->snort_alert_score,
            'tcp_connection_score' => (float) $this->tcp_connection_score,
            'baseline_deviation_score' => (float) $this->baseline_deviation_score,
            'ai_confidence_score' => (float) $this->ai_confidence_score,
        ];

        $rawRadar = is_array($this->raw_features) && is_array($this->raw_features['radar_scores'] ?? null)
            ? $this->raw_features['radar_scores']
            : [];

        foreach ($rawRadar as $key => $value) {
            if (is_numeric($value)) {
                $scores[$key] = (float) $value;
            }
        }

        return $scores;
    }
}
