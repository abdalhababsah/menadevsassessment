<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function log(string $action, Model $auditable, ?array $changes = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'changes' => $changes,
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
