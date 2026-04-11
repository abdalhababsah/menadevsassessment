<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'actor_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string', 'max:100'],
            'auditable_type' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $paginator = AuditLog::query()
            ->with('user:id,name,email')
            ->when($filters['actor_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['action'] ?? null, fn ($q, $action) => $q->where('action', 'like', $action.'%'))
            ->when(
                $filters['auditable_type'] ?? null,
                fn ($q, $type) => $q->where('auditable_type', 'like', '%'.$type.'%'),
            )
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('created_at', '<=', $to))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        /** @var array<int, array<string, mixed>> $logs */
        $logs = [];
        foreach ($paginator->getCollection() as $log) {
            /** @var AuditLog $log */
            $logs[] = [
                'id' => $log->id,
                'action' => $log->action,
                'auditable_type' => class_basename($log->auditable_type),
                'auditable_id' => $log->auditable_id,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601String(),
                'actor' => $log->user !== null ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
            ];
        }

        return Inertia::render('Admin/AuditLog/Index', [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => [
                'actor_id' => $filters['actor_id'] ?? null,
                'action' => $filters['action'] ?? null,
                'auditable_type' => $filters['auditable_type'] ?? null,
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
            ],
        ]);
    }

    public function show(AuditLog $auditLog): Response
    {
        $auditLog->load('user:id,name,email');

        return Inertia::render('Admin/AuditLog/Show', [
            'log' => [
                'id' => $auditLog->id,
                'action' => $auditLog->action,
                'auditable_type' => $auditLog->auditable_type,
                'auditable_type_short' => class_basename($auditLog->auditable_type),
                'auditable_id' => $auditLog->auditable_id,
                'ip_address' => $auditLog->ip_address,
                'created_at' => $auditLog->created_at?->toIso8601String(),
                'changes' => $auditLog->changes,
                'actor' => $auditLog->user !== null ? [
                    'id' => $auditLog->user->id,
                    'name' => $auditLog->user->name,
                    'email' => $auditLog->user->email,
                ] : null,
            ],
        ]);
    }
}
