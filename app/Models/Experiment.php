<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Experiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_code',
        'name',
        'experiment_date',
        'network_interface',
        'target_ip',
        'source_ip',
        'capture_duration',
        'notes',
        'scenario_key',
        'traffic_type',
        'status',
        'experiment_status',
        'ground_truth_label',
        'tool_profile',
        'attack_pattern',
        'analysis_profile_key',
        'target_platform',
        'user_id',
    ];

    protected $casts = [
        'experiment_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function acquisitionFiles(): HasMany
    {
        return $this->hasMany(AcquisitionFile::class);
    }

    public function validationFiles(): HasMany
    {
        return $this->hasMany(ValidationFile::class);
    }

    public function snortAlerts(): HasMany
    {
        return $this->hasMany(SnortAlert::class);
    }

    public function extractedFeature(): HasOne
    {
        return $this->hasOne(ExtractedFeature::class);
    }

    public function aiResults(): HasMany
    {
        return $this->hasMany(AiResult::class);
    }

    public function finalReports(): HasMany
    {
        return $this->hasMany(FinalReport::class);
    }

    public function reviewerNotes(): HasMany
    {
        return $this->hasMany(ReviewerNote::class);
    }
}
