<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobCard;
use App\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $garageId = \App\Models\Garage::getActiveGarageId();

        // If super admin and no garage selected, redirect to garages index
        if (auth()->user()->role === 'super_admin' && !$garageId) {
            return redirect()->route('garages.index');
        }

        if (auth()->user()->role === 'customer') {
            $customerProfile = auth()->user()->customerProfile;
            
            if (!$customerProfile) {
                return Inertia::render('CustomerDashboard', [
                    'metrics' => [
                        'total_vehicles' => 0,
                        'active_jobs' => 0,
                        'total_expenditure' => 0,
                    ],
                    'recentJobs' => [],
                    'vehicles' => [],
                    'brands' => [],
                    'models' => [],
                ]);
            }

            $garageId = $customerProfile->garage_id;

            $totalVehicles = \App\Models\Vehicle::where('customer_id', $customerProfile->id)->count();
            
            $activeJobs = JobCard::where('customer_id', $customerProfile->id)
                ->whereIn('status', ['scheduled', 'listed', 'working'])
                ->count();
                
            $totalExpenditure = JobCard::where('customer_id', $customerProfile->id)
                ->whereIn('status', ['complete', 'completed', 'delivered'])
                ->sum('final_total');

            $recentJobs = JobCard::with(['vehicle.brand', 'vehicle.vehicleModel'])
                ->where('customer_id', $customerProfile->id)
                ->latest()
                ->take(5)
                ->get();

            $vehicles = \App\Models\Vehicle::with(['brand', 'vehicleModel'])
                ->where('customer_id', $customerProfile->id)
                ->get();

            $brands = \App\Models\VehicleBrand::where('garage_id', $garageId)->orWhereNull('garage_id')->get();
            $models = \App\Models\VehicleModel::where('garage_id', $garageId)->orWhereNull('garage_id')->with('brand')->get();

            return Inertia::render('CustomerDashboard', [
                'metrics' => [
                    'total_vehicles' => $totalVehicles,
                    'active_jobs' => $activeJobs,
                    'total_expenditure' => $totalExpenditure,
                ],
                'recentJobs' => $recentJobs,
                'vehicles' => $vehicles,
                'brands' => $brands,
                'models' => $models,
            ]);
        }

        // Metrics
        $totalJobCards = JobCard::where('garage_id', $garageId)->count();
        $pendingJobCards = JobCard::where('garage_id', $garageId)->where('status', 'listed')->count();

        $totalCustomers = Customer::where('garage_id', $garageId)->count();
        $newCustomersThisMonth = Customer::where('garage_id', $garageId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->count();

        $totalRevenue = JobCard::where('garage_id', $garageId)
            ->whereIn('status', ['complete', 'completed', 'delivered'])
            ->sum('final_total');
            
        $activeMechanics = User::where('garage_id', $garageId)
            ->where('role', 'mechanic')
            ->where('is_active', true)
            ->count();

        // Lists
        $recentJobCards = JobCard::with(['customer.user', 'vehicle.brand', 'vehicle.vehicleModel'])
            ->where('garage_id', $garageId)
            ->latest()
            ->take(5)
            ->get();

        $recentCustomers = Customer::with('user')
            ->where('garage_id', $garageId)
            ->latest()
            ->take(5)
            ->get();

        $mechanicsWorkload = User::where('garage_id', $garageId)
            ->whereIn('role', ['mechanic', 'manager'])
            ->where('is_active', true)
            ->with(['assignedJobs' => function($q) {
                $q->whereIn('status', ['scheduled', 'listed', 'working']);
            }])
            ->get()
            ->map(function($mechanic) {
                return [
                    'id' => $mechanic->id,
                    'name' => $mechanic->name,
                    'role' => $mechanic->role,
                    'active_jobs_count' => $mechanic->assignedJobs->count(),
                    'active_jobs' => $mechanic->assignedJobs->map(function($job) {
                        return [
                            'job_number' => $job->job_number,
                            'status' => $job->status,
                            'priority' => $job->priority,
                        ];
                    })
                ];
            });

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_jobs' => $totalJobCards,
                'pending_jobs' => $pendingJobCards,
                'total_customers' => $totalCustomers,
                'new_customers_month' => $newCustomersThisMonth,
                'total_revenue' => $totalRevenue,
                'active_mechanics' => $activeMechanics,
            ],
            'recentJobs' => $recentJobCards,
            'recentCustomers' => $recentCustomers,
            'mechanicsWorkload' => $mechanicsWorkload,
        ]);
    }
}
