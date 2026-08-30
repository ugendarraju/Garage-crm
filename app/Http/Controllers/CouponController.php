<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->get()->map(function ($coupon) {
            $coupon->status_label = $coupon->status;
            return $coupon;
        });

        return Inertia::render('Coupons/Index', [
            'coupons' => $coupons
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $garageId = \App\Models\Garage::getActiveGarageId();

        // Check if code exists in this garage
        if (Coupon::where('garage_id', $garageId)->where('code', strtoupper($request->code))->exists()) {
            return redirect()->back()->withErrors(['code' => 'Coupon code already exists.']);
        }

        Coupon::create([
            'garage_id' => $garageId,
            'code' => strtoupper($request->code),
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->back()->with('success', 'Coupon created successfully.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        if ((int) $coupon->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
            abort(403);
        }

        $request->validate([
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'is_active' => 'boolean',
        ]);

        $coupon->update([
            'discount_type' => $request->discount_type,
            'discount_value' => $request->discount_value,
            'expiry_date' => $request->expiry_date,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->back()->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        if ((int) $coupon->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
            abort(403);
        }

        $coupon->delete();
        return redirect()->back()->with('success', 'Coupon deleted successfully.');
    }

    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0'
        ]);

        $garageId = \App\Models\Garage::getActiveGarageId();
        
        $coupon = Coupon::where('garage_id', $garageId)
            ->where('code', strtoupper($request->code))
            ->first();

        if (!$coupon) {
            return response()->json(['error' => 'Invalid coupon code.'], 400);
        }

        if ($coupon->status !== 'Active') {
            return response()->json(['error' => 'This coupon is ' . strtolower($coupon->status) . '.'], 400);
        }

        $discount = $coupon->calculateDiscount($request->subtotal);

        return response()->json([
            'success' => true,
            'discount' => round($discount, 2),
            'code' => $coupon->code,
            'message' => 'Coupon applied successfully!'
        ]);
    }

    public function toggleActive(Coupon $coupon)
    {
        if ((int) $coupon->garage_id !== (int) \App\Models\Garage::getActiveGarageId()) {
            abort(403);
        }

        $coupon->update([
            'is_active' => !$coupon->is_active
        ]);

        return redirect()->back()->with('success', 'Coupon status updated.');
    }
}
