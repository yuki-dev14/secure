<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditLog::with('user')->latest('created_at');

        if ($request->filled('event'))   $query->where('event', $request->event);
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'ilike', "%{$s}%")
                  ->orWhere('ip_address', 'ilike', "%{$s}%");
            });
        }
        if ($request->filled('date_from')) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $query->whereDate('created_at', '<=', $request->date_to);

        $logs      = $query->paginate(50)->withQueryString();
        $events    = AuditLog::distinct()->orderBy('event')->pluck('event');
        $summary   = [
            'today'    => AuditLog::whereDate('created_at', today())->count(),
            'logins'   => AuditLog::where('event', 'login')->count(),
            'fraud'    => AuditLog::where('event', 'double_claim_attempt')->count(),
        ];

        return Inertia::render('Superadmin/AuditLogs/Index', compact('logs', 'events', 'summary'));
    }

    public function show(int $id): Response
    {
        $log = AuditLog::with('user')->findOrFail($id);
        return Inertia::render('Superadmin/AuditLogs/Show', compact('log'));
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $rows = AuditLog::with('user')
            ->when($request->date_from, fn($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to,   fn($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest('created_at')
            ->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit_log_'.now()->format('Ymd_His').'.csv"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID','Event','User','Role','Description','IP','Model','Date']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->event,
                    $row->user?->name ?? 'Guest',
                    $row->user_type,
                    $row->description,
                    $row->ip_address,
                    $row->auditable_type ? class_basename($row->auditable_type).'#'.$row->auditable_id : '',
                    $row->created_at?->format('Y-m-d H:i:s'),
                ]);
            }
            fclose($handle);
        }, 'audit_log.csv', $headers);
    }
}
