<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public function log(string $action, ?Model $subject = null, array $meta = []): void
    {
        AuditLog::create([
            'user_id'      => Auth::id(),
            'action'       => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id'   => $subject?->getKey(),
            'ip_address'   => Request::ip(),
            'user_agent'   => substr((string) Request::userAgent(), 0, 250),
            'meta'         => $meta,
        ]);
    }
}
