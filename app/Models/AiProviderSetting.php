<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiProviderSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_key',
        'provider_label',
        'driver',
        'api_key',
        'api_url',
        'model',
        'use_live_api',
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'use_live_api' => 'boolean',
    ];
}
