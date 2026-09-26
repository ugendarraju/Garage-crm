<?php

namespace App\Http\Controllers;

use App\Models\ActionLog;
use App\Models\Garage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActionLogController extends Controller
{
    private function authorizeAccess(): void
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeAccess();

        $user = auth()->user();
        $isSuperAdminMaster = $user->role === 'super_admin' && !session()->has('active_garage_id');
        $activeGarageId = Garage::getActiveGarageId();

        // Target garage ID filter
        $garageId = null;
        if ($isSuperAdminMaster) {
            $garageId = $request->query('garage_id') ? (int) $request->query('garage_id') : null;
        } else {
            $garageId = $activeGarageId;
        }

        $query = ActionLog::query()->with(['user:id,name,role', 'garage:id,name']);

        if ($garageId !== null) {
            $query->where('garage_id', $garageId);
        }

        // Apply filters
        if ($request->filled('module') && $request->module !== 'all') {
            $query->where('module', $request->module);
        }

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_range')) {
            $now = Carbon::now();
            switch ($request->date_range) {
                case 'today':
                    $query->whereDate('created_at', Carbon::today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', Carbon::yesterday());
                    break;
                case 'this_week':
                    $query->where('created_at', '>=', $now->copy()->subDays(7)->startOfDay());
                    break;
                case 'this_month':
                    $query->where('created_at', '>=', $now->copy()->startOfMonth());
                    break;
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Return JSON if requested by modal or API call
        if ($request->wantsJson() || $request->query('format') === 'json') {
            $logs = $query->orderBy('created_at', 'desc')->paginate(20);
            return response()->json([
                'logs' => $logs,
            ]);
        }

        // Stats calculation
        $statsQuery = ActionLog::query();
        if ($garageId !== null) {
            $statsQuery->where('garage_id', $garageId);
        }

        $totalCount = (clone $statsQuery)->count();
        $todayCount = (clone $statsQuery)->whereDate('created_at', Carbon::today())->count();
        $thisWeekCount = (clone $statsQuery)->where('created_at', '>=', Carbon::now()->subDays(7)->startOfDay())->count();
        $criticalCount = (clone $statsQuery)->whereIn('action', ['deleted', 'restored', 'disabled', 'reset_password'])->count();

        $stats = [
            'total' => $totalCount,
            'today' => $todayCount,
            'this_week' => $thisWeekCount,
            'critical' => $criticalCount,
        ];

        $logs = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();

        $garages = $isSuperAdminMaster
            ? Garage::select('id', 'name')->orderBy('name')->get()
            : [];

        // Users in current scope for filter dropdown
        $usersQuery = User::select('id', 'name', 'role')->orderBy('name');
        if ($garageId !== null) {
            $usersQuery->where('garage_id', $garageId);
        }
        $users = $usersQuery->get();

        return Inertia::render('ActionLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'module', 'action', 'user_id', 'date_range', 'garage_id']),
            'garages' => $garages,
            'users' => $users,
            'stats' => $stats,
            'isMasterAdmin' => $isSuperAdminMaster,
            'selectedGarageId' => $garageId,
        ]);
    }

    public function garageLogs(Request $request, Garage $garage)
    {
        $this->authorizeAccess();

        $user = auth()->user();
        if ($user->role !== 'super_admin' && $user->garage_id !== $garage->id) {
            abort(403, 'Unauthorized access to this garage action logs.');
        }

        $query = ActionLog::where('garage_id', $garage->id)
            ->with(['user:id,name,role']);

        if ($request->filled('module') && $request->module !== 'all') {
            $query->where('module', $request->module);
        }

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'garage' => [
                'id' => $garage->id,
                'name' => $garage->name,
            ],
            'logs' => $logs,
            'total' => ActionLog::where('garage_id', $garage->id)->count(),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorizeAccess();

        $user = auth()->user();
        $isSuperAdminMaster = $user->role === 'super_admin' && !session()->has('active_garage_id');
        $activeGarageId = Garage::getActiveGarageId();

        $garageId = $isSuperAdminMaster ? ($request->query('garage_id') ? (int)$request->query('garage_id') : null) : $activeGarageId;

        $query = ActionLog::query()->with('garage:id,name');
        if ($garageId !== null) {
            $query->where('garage_id', $garageId);
        }

        if ($request->filled('module') && $request->module !== 'all') {
            $query->where('module', $request->module);
        }

        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        $filename = 'action_logs_' . ($garageId ? "garage_{$garageId}_" : 'all_') . Carbon::now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Timestamp', 'Garage', 'User', 'Role', 'Action', 'Module', 'Description', 'IP Address']);

            $query->orderBy('created_at', 'desc')->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->id,
                        $log->created_at->format('Y-m-d H:i:s'),
                        $log->garage ? $log->garage->name : 'System',
                        $log->user_name ?: 'System',
                        $log->user_role ?: 'N/A',
                        $log->action,
                        $log->module,
                        $log->description,
                        $log->ip_address ?: 'N/A',
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
