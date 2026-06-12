<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AuditLog::with('user')->latest()->paginate(30);
        return view('audit.index', compact('logs'));
    }
}
