<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\JobCard;
use App\Models\PurchaseInvoice;
use App\Models\Garage;
use App\Services\DatabaseSchemaAutoFixService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseController extends Controller
{
    /**
     * Restrict access strictly to Garage Owner and Super Admin.
     */
    private function checkOwnerAccess(): void
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['owner', 'super_admin'])) {
            abort(403, 'Unauthorized access. Workshop expenses and true profit analytics are reserved for garage owners only.');
        }
    }

    public function index(Request $request)
    {
        $this->checkOwnerAccess();
        DatabaseSchemaAutoFixService::ensureSchemaIsUpToDate();

        $garageId = Garage::getActiveGarageId();

        // Date range filter
        $range = $request->input('range', 'this_month');
        $startDate = null;
        $endDate = Carbon::today()->format('Y-m-d');

        if ($range === 'today') {
            $startDate = Carbon::today()->format('Y-m-d');
        } elseif ($range === 'this_week') {
            $startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
        } elseif ($range === 'last_month') {
            $startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
        } elseif ($range === 'custom' && $request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
        } else {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $range = 'this_month';
        }

        $revenue = 0.0;
        $partsCost = 0.0;
        $operatingExpenses = 0.0;
        $grossProfit = 0.0;
        $netProfit = 0.0;
        $netProfitMargin = 0.0;
        $categoryBreakdown = [];
        $expenses = new LengthAwarePaginator([], 0, 25);

        try {
            // 1. Invoiced Workshop Revenue from completed Job Cards
            if (Schema::hasTable('job_cards')) {
                $revenue = (float) JobCard::where('garage_id', $garageId)
                    ->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate)
                    ->whereNotIn('status', ['scheduled']) // exclude draft estimates
                    ->sum('final_total');
            }

            // 2. Parts / COGS from Purchase Invoices
            if (Schema::hasTable('purchase_invoices')) {
                $partsCost = (float) PurchaseInvoice::where('garage_id', $garageId)
                    ->whereDate('purchase_date', '>=', $startDate)
                    ->whereDate('purchase_date', '<=', $endDate)
                    ->sum('total_amount');
            }

            // 3. Workshop Operating Expenses
            if (Schema::hasTable('expenses')) {
                $expensesQuery = Expense::where('garage_id', $garageId)
                    ->whereDate('expense_date', '>=', $startDate)
                    ->whereDate('expense_date', '<=', $endDate);

                $operatingExpenses = (float) (clone $expensesQuery)->sum('amount');

                // Category breakdown
                $categoryBreakdown = (clone $expensesQuery)
                    ->selectRaw('category, SUM(amount) as total')
                    ->groupBy('category')
                    ->pluck('total', 'category')
                    ->toArray();

                // Expense listing with search and category filtering
                $listQuery = (clone $expensesQuery)->with('creator');

                if ($cat = $request->input('category')) {
                    $listQuery->where('category', $cat);
                }

                if ($search = $request->input('search')) {
                    $listQuery->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('notes', 'like', "%{$search}%");
                    });
                }

                $expenses = $listQuery->orderBy('expense_date', 'desc')->latest()->paginate(25)->withQueryString();
            }

            // Net Profit & Profit Margin
            $grossProfit = $revenue - $partsCost;
            $netProfit = $revenue - $partsCost - $operatingExpenses;
            $netProfitMargin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 1) : 0.0;
        } catch (\Throwable $e) {
            Log::error('ExpenseController index error: ' . $e->getMessage());
        }

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'summary' => [
                'revenue' => $revenue,
                'parts_cost' => $partsCost,
                'operating_expenses' => $operatingExpenses,
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'net_profit_margin' => $netProfitMargin,
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'filters' => [
                'range' => $range,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'category' => $request->input('category', ''),
                'search' => $request->input('search', ''),
            ],
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $this->checkOwnerAccess();
        DatabaseSchemaAutoFixService::ensureSchemaIsUpToDate();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|in:cash,upi,bank_transfer',
            'notes' => 'nullable|string',
        ]);

        $garageId = Garage::getActiveGarageId();

        Expense::create([
            'garage_id' => $garageId,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'category' => $validated['category'],
            'expense_date' => $validated['expense_date'],
            'payment_method' => $validated['payment_method'] ?? 'cash',
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Expense recorded successfully.');
    }

    public function update(Request $request, Expense $expense)
    {
        $this->checkOwnerAccess();
        $garageId = Garage::getActiveGarageId();

        if (auth()->user()->role !== 'super_admin' && (int) $expense->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'category' => 'required|string',
            'expense_date' => 'required|date',
            'payment_method' => 'nullable|string|in:cash,upi,bank_transfer',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return back()->with('success', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $this->checkOwnerAccess();
        $garageId = Garage::getActiveGarageId();

        if (auth()->user()->role !== 'super_admin' && (int) $expense->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized.');
        }

        $expense->delete();

        return back()->with('success', 'Expense removed successfully.');
    }
}
