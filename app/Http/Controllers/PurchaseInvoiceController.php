<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Inventory;
use App\Models\Garage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    private function authorizeStaff(): void
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            abort(403, 'Unauthorized access.');
        }
    }

    public function index()
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();

        $invoices = PurchaseInvoice::where('garage_id', $garageId)
            ->with('supplier')
            ->orderBy('purchase_date', 'desc')
            ->get();

        return Inertia::render('Purchases/Index', [
            'invoices' => $invoices
        ]);
    }

    public function create()
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();

        $suppliers = Supplier::where('garage_id', $garageId)->orderBy('name')->get();
        $inventoryItems = Inventory::where('garage_id', $garageId)->orderBy('part_name')->get();

        return Inertia::render('Purchases/Create', [
            'suppliers' => $suppliers,
            'inventoryItems' => $inventoryItems
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'total_amount' => 'required|numeric',
            'status' => 'required|in:paid,unpaid,partial',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'nullable|exists:inventories,id',
            'items.*.part_name' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric',
            'items.*.apply_gst' => 'nullable|boolean',
            'items.*.gst_percent' => 'nullable|string',
            'bill_image' => 'nullable|image|max:5120', // max 5MB
            'payment_receipt' => 'nullable|image|max:5120', // max 5MB
        ]);

        // Cross-tenant verification: verify supplier belongs to active garage
        if (auth()->user()->role !== 'super_admin') {
            Supplier::where('id', $validated['supplier_id'])->where('garage_id', $garageId)->firstOrFail();
        }

        $billImagePath = null;
        if ($request->hasFile('bill_image')) {
            $billImagePath = \App\Services\GarageStorageService::store($request->file('bill_image'), 'purchases/bills', $garageId);
        }

        $paymentReceiptPath = null;
        if ($request->hasFile('payment_receipt')) {
            $paymentReceiptPath = \App\Services\GarageStorageService::store($request->file('payment_receipt'), 'purchases/receipts', $garageId);
        }

        DB::transaction(function () use ($validated, $billImagePath, $paymentReceiptPath, $garageId) {
            $invoice = PurchaseInvoice::create([
                'garage_id' => $garageId,
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'purchase_date' => $validated['purchase_date'],
                'total_amount' => $validated['total_amount'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'bill_image_path' => $billImagePath,
                'payment_receipt_path' => $paymentReceiptPath,
            ]);

            foreach ($validated['items'] as $itemData) {
                // Determine inventory id
                $inventoryId = $itemData['inventory_id'] ?? null;
                $partName = $itemData['part_name'] ?? '';

                if ($inventoryId && auth()->user()->role !== 'super_admin') {
                    Inventory::where('id', $inventoryId)->where('garage_id', $garageId)->firstOrFail();
                }

                if (!$inventoryId && $partName) {
                    // Create new inventory item scoped to garage
                    $newInventory = Inventory::create([
                        'garage_id' => $garageId,
                        'part_name' => $partName,
                        'current_stock' => 0,
                        'current_price' => $itemData['unit_price'],
                        'category' => 'General',
                        'min_stock_level' => 5,
                    ]);
                    $inventoryId = $newInventory->id;
                }

                // Create invoice item
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'inventory_id' => $inventoryId,
                    'part_name' => $partName ?: ($inventoryId ? Inventory::find($inventoryId)->part_name : 'Item'),
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'gst_percent' => !empty($itemData['apply_gst']) ? ($itemData['gst_percent'] ?? null) : null,
                    'total_price' => $itemData['total_price'],
                ]);

                // Update inventory stock
                if ($inventoryId) {
                    $inv = Inventory::find($inventoryId);
                    if ($inv) {
                        $inv->current_stock += $itemData['quantity'];
                        $inv->current_price = $itemData['unit_price'];
                        $inv->save();
                    }
                }
            }

            // Update supplier balance if unpaid or partial
            if ($validated['status'] !== 'paid') {
                $supplier = Supplier::find($validated['supplier_id']);
                if ($supplier) {
                    $supplier->balance += $validated['total_amount'];
                    $supplier->save();
                }
            }
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase invoice saved successfully and inventory updated.');
    }

    public function edit(PurchaseInvoice $purchase)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $purchase->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $purchase->load(['supplier', 'items']);
        $suppliers = Supplier::where('garage_id', $garageId)->orderBy('name')->get();
        $inventoryItems = Inventory::where('garage_id', $garageId)->orderBy('part_name')->get();

        return Inertia::render('Purchases/Edit', [
            'invoice' => $purchase,
            'suppliers' => $suppliers,
            'inventoryItems' => $inventoryItems,
        ]);
    }

    public function update(Request $request, PurchaseInvoice $purchase)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $purchase->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'invoice_number' => 'required|string|max:255|unique:purchase_invoices,invoice_number,' . $purchase->id . ',id,garage_id,' . $garageId,
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'status' => 'required|in:paid,unpaid,partial',
            'notes' => 'nullable|string',
            'total_amount' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.inventory_id' => 'nullable|exists:inventories,id',
            'items.*.part_name' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.total_price' => 'required|numeric',
            'items.*.apply_gst' => 'nullable|boolean',
            'items.*.gst_percent' => 'nullable|string',
            'bill_image' => 'nullable|image|max:5120',
            'payment_receipt' => 'nullable|image|max:5120',
        ]);

        if (auth()->user()->role !== 'super_admin') {
            Supplier::where('id', $validated['supplier_id'])->where('garage_id', $garageId)->firstOrFail();
        }

        $oldStatus = $purchase->status;
        $newStatus = $validated['status'];
        $oldTotal = $purchase->total_amount;
        $newTotal = $validated['total_amount'];

        $billImagePath = $purchase->bill_image_path;
        if ($request->hasFile('bill_image')) {
            if ($purchase->bill_image_path) {
                \App\Services\GarageStorageService::delete($purchase->bill_image_path);
            }
            $billImagePath = \App\Services\GarageStorageService::store($request->file('bill_image'), 'purchases/bills', $garageId);
        }

        $paymentReceiptPath = $purchase->payment_receipt_path;
        if ($request->hasFile('payment_receipt')) {
            if ($purchase->payment_receipt_path) {
                \App\Services\GarageStorageService::delete($purchase->payment_receipt_path);
            }
            $paymentReceiptPath = \App\Services\GarageStorageService::store($request->file('payment_receipt'), 'purchases/receipts', $garageId);
        }

        DB::transaction(function () use ($purchase, $oldStatus, $newStatus, $oldTotal, $newTotal, $validated, $billImagePath, $paymentReceiptPath, $garageId) {
            // Revert old invoice effects
            if ($oldStatus !== 'paid') {
                $supplier = Supplier::find($purchase->supplier_id);
                if ($supplier) {
                    $supplier->balance -= $oldTotal;
                    $supplier->save();
                }
            }

            // Revert inventory from old items
            foreach ($purchase->items as $oldItem) {
                if ($oldItem->inventory_id) {
                    $inv = Inventory::find($oldItem->inventory_id);
                    if ($inv) {
                        $inv->current_stock -= $oldItem->quantity;
                        $inv->save();
                    }
                }
            }

            // Delete old items
            $purchase->items()->delete();

            // Update the invoice
            $purchase->update([
                'invoice_number' => $validated['invoice_number'],
                'supplier_id' => $validated['supplier_id'],
                'purchase_date' => $validated['purchase_date'],
                'status' => $newStatus,
                'notes' => $validated['notes'],
                'total_amount' => $newTotal,
                'bill_image_path' => $billImagePath,
                'payment_receipt_path' => $paymentReceiptPath,
            ]);

            // Add new items
            foreach ($validated['items'] as $itemData) {
                $inventoryId = $itemData['inventory_id'] ?? null;
                $partName = $itemData['part_name'] ?? '';

                if ($inventoryId && auth()->user()->role !== 'super_admin') {
                    Inventory::where('id', $inventoryId)->where('garage_id', $garageId)->firstOrFail();
                }

                if (!$inventoryId && $partName) {
                    $newInventory = Inventory::create([
                        'garage_id' => $garageId,
                        'part_name' => $partName,
                        'current_stock' => 0,
                        'current_price' => $itemData['unit_price'],
                        'category' => 'General',
                        'min_stock_level' => 5,
                    ]);
                    $inventoryId = $newInventory->id;
                }

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $purchase->id,
                    'inventory_id' => $inventoryId,
                    'part_name' => $partName ?: ($inventoryId ? Inventory::find($inventoryId)->part_name : 'Item'),
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'gst_percent' => !empty($itemData['apply_gst']) ? ($itemData['gst_percent'] ?? null) : null,
                    'total_price' => $itemData['total_price'],
                ]);

                if ($inventoryId) {
                    $inv = Inventory::find($inventoryId);
                    if ($inv) {
                        $inv->current_stock += $itemData['quantity'];
                        $inv->current_price = $itemData['unit_price'];
                        $inv->save();
                    }
                }
            }

            // Apply new invoice effects
            if ($newStatus !== 'paid') {
                $supplier = Supplier::find($purchase->supplier_id);
                if ($supplier) {
                    $supplier->balance += $newTotal;
                    $supplier->save();
                }
            }
        });

        return redirect()->route('purchases.index')->with('success', 'Purchase invoice updated successfully.');
    }
}
