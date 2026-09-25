<?php

namespace App\Http\Controllers;

use App\Models\JobCard;
use App\Models\Garage;
use App\Services\DatabaseSchemaAutoFixService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;

class ServiceReminderController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }

        DatabaseSchemaAutoFixService::ensureSchemaIsUpToDate();

        $garageId = Garage::getActiveGarageId();
        $garage = $garageId ? Garage::find($garageId) : null;
        $today = Carbon::today()->format('Y-m-d');
        $in7Days = Carbon::today()->addDays(7)->format('Y-m-d');
        $in30Days = Carbon::today()->addDays(30)->format('Y-m-d');

        $hasReminderCol = Schema::hasColumn('job_cards', 'next_service_reminder');
        $hasSentCol = Schema::hasColumn('job_cards', 'service_reminder_sent_at');

        // Base query
        $query = JobCard::with(['vehicle.brand', 'vehicle.vehicleModel', 'customer.user']);

        if ($garageId) {
            $query->where('garage_id', $garageId);
        }

        if ($hasReminderCol) {
            $query->whereNotNull('next_service_reminder')
                  ->where('next_service_reminder', '!=', '')
                  ->where('next_service_reminder', '!=', '0000-00-00');
        } else {
            $query->whereRaw('1 = 0');
        }

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', function ($vq) use ($search) {
                    $vq->where('registration_number', 'like', "%{$search}%");
                })->orWhereHas('customer.user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        // Status tab filter
        $filter = $request->input('filter', 'all');
        if ($hasReminderCol) {
            if ($filter === 'overdue') {
                $query->where('next_service_reminder', '<', $today);
            } elseif ($filter === 'this_week') {
                $query->whereBetween('next_service_reminder', [$today, $in7Days]);
            } elseif ($filter === 'this_month') {
                $query->whereBetween('next_service_reminder', [$in7Days, $in30Days]);
            } elseif ($filter === 'sent') {
                if ($hasSentCol) {
                    $query->whereNotNull('service_reminder_sent_at');
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        $reminders = $query->orderBy('next_service_reminder', 'asc')->get();

        // Calculate KPI Counts across all valid records for this garage
        $stats = [
            'total' => 0,
            'overdue' => 0,
            'this_week' => 0,
            'this_month' => 0,
            'sent' => 0,
        ];

        if ($hasReminderCol) {
            $baseStatsQuery = JobCard::query();
            if ($garageId) {
                $baseStatsQuery->where('garage_id', $garageId);
            }
            $baseStatsQuery->whereNotNull('next_service_reminder')
                           ->where('next_service_reminder', '!=', '')
                           ->where('next_service_reminder', '!=', '0000-00-00');

            $stats['total'] = (clone $baseStatsQuery)->count();
            $stats['overdue'] = (clone $baseStatsQuery)->where('next_service_reminder', '<', $today)->count();
            $stats['this_week'] = (clone $baseStatsQuery)->whereBetween('next_service_reminder', [$today, $in7Days])->count();
            $stats['this_month'] = (clone $baseStatsQuery)->whereBetween('next_service_reminder', [$in7Days, $in30Days])->count();

            if ($hasSentCol) {
                $stats['sent'] = (clone $baseStatsQuery)->whereNotNull('service_reminder_sent_at')->count();
            }
        }

        return Inertia::render('ServiceReminders/Index', [
            'reminders' => $reminders,
            'stats' => $stats,
            'currentFilter' => $filter,
            'search' => $search ?? '',
            'garage' => [
                'name' => $garage?->name,
                'slug' => $garage?->slug,
                'phone' => $garage?->phone,
                'default_service_reminder_days' => $garage?->default_service_reminder_days ?: 90,
            ],
            'today' => $today,
        ]);
    }

    public function markSent(JobCard $jobCard)
    {
        DatabaseSchemaAutoFixService::ensureSchemaIsUpToDate();

        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $jobCard->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized.');
        }

        $jobCard->update([
            'service_reminder_sent_at' => now(),
        ]);

        return back()->with('success', 'Reminder marked as sent.');
    }
}
