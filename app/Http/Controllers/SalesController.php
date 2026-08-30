<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JobCard;
use Inertia\Inertia;

class SalesController extends Controller
{
    /**
     * Display a listing of sales (delivered job cards).
     */
    public function index(Request $request)
    {
        $garageId = \App\Models\Garage::getActiveGarageId();

        // If super admin and no garage selected, redirect to garages index
        if (auth()->user()->role === 'super_admin' && !$garageId) {
            return redirect()->route('garages.index');
        }

        // Fetch delivered / completed job cards
        $salesQuery = JobCard::with(['customer.user', 'vehicle.brand', 'vehicle.vehicleModel'])
            ->where('garage_id', $garageId)
            ->whereIn('status', ['delivered', 'completed', 'complete']);

        // Search filtering (optional, allows searching by customer or job number)
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $salesQuery->where(function ($q) use ($search) {
                $q->where('job_number', 'like', "%{$search}%")
                  ->orWhereHas('customer.user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('vehicle', function ($q3) use ($search) {
                      $q3->where('registration_number', 'like', "%{$search}%");
                  });
            });
        }

        // Month filtering
        if ($request->has('month') && $request->input('month')) {
            $month = $request->input('month');
            $parts = explode('-', $month);
            if (count($parts) === 2) {
                $salesQuery->whereYear('updated_at', $parts[0])
                           ->whereMonth('updated_at', $parts[1]);
            }
        }

        // Calculate total revenue from filtered jobs for this garage
        $totalRevenue = (clone $salesQuery)->sum('final_total');

        $sales = $salesQuery->latest('updated_at')->paginate(15)->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'totalRevenue' => (float)$totalRevenue,
            'filters' => $request->only('search', 'month'),
        ]);
    }
}
