<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Http\Requests\StoreInventoryRequest;
use App\Http\Requests\UpdateInventoryRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Models\PartCategory;
use App\Models\PartBrand;
use App\Models\Garage;

class InventoryController extends Controller
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

        $inventory = Inventory::where('garage_id', $garageId)->orderBy('part_name')->get();
        $categories = PartCategory::where('garage_id', $garageId)->orderBy('name')->get();
        $brands = PartBrand::where('garage_id', $garageId)->orderBy('name')->get();
        
        return Inertia::render('Inventory/Index', [
            'inventory' => $inventory,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function create()
    {
        $this->authorizeStaff();
        return Inertia::render('Inventory/Create');
    }

    public function store(StoreInventoryRequest $request)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();

        $validated = $request->validate([
            'part_name' => 'required|string|max:255',
            'part_code' => 'nullable|string|max:100|unique:inventories,part_code,NULL,id,garage_id,' . $garageId,
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'current_stock' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'current_price' => 'required|numeric|min:0',
            'gst_rate' => 'required|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
        ]);

        $validated['garage_id'] = $garageId;

        $item = Inventory::create($validated);

        \App\Services\ActionLogService::log(
            action: 'created',
            module: 'inventory',
            description: "Added inventory item '{$item->part_name}' (Stock: {$item->current_stock}, Price: ₹{$item->current_price})",
            garageId: $garageId,
            details: ['inventory_id' => $item->id, 'part_code' => $item->part_code, 'stock' => $item->current_stock, 'price' => $item->current_price]
        );

        return redirect()->route('inventory.index')->with('success', 'Item added to inventory.');
    }

    public function show(Inventory $inventory)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $inventory->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        return Inertia::render('Inventory/Show', [
            'item' => $inventory
        ]);
    }

    public function edit(Inventory $inventory)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $inventory->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        return Inertia::render('Inventory/Edit', [
            'item' => $inventory
        ]);
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $inventory->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'part_name' => 'required|string|max:255',
            'part_code' => 'nullable|string|max:100|unique:inventories,part_code,'.$inventory->id.',id,garage_id,'.$garageId,
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'current_stock' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'current_price' => 'required|numeric|min:0',
            'gst_rate' => 'required|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
        ]);

        $inventory->update($validated);

        \App\Services\ActionLogService::log(
            action: 'updated',
            module: 'inventory',
            description: "Updated inventory item '{$inventory->part_name}' (Stock: {$inventory->current_stock}, Price: ₹{$inventory->current_price})",
            garageId: $garageId,
            details: ['inventory_id' => $inventory->id, 'part_code' => $inventory->part_code, 'stock' => $inventory->current_stock]
        );

        return redirect()->route('inventory.index')->with('success', 'Inventory item updated.');
    }

    public function destroy(Inventory $inventory)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();
        if (auth()->user()->role !== 'super_admin' && (int) $inventory->garage_id !== (int) $garageId) {
            abort(403, 'Unauthorized access.');
        }

        $partName = $inventory->part_name;
        \App\Services\ActionLogService::log(
            action: 'deleted',
            module: 'inventory',
            description: "Deleted inventory item '{$partName}'",
            garageId: $garageId,
            details: ['inventory_id' => $inventory->id, 'part_name' => $partName]
        );

        $inventory->delete();
        return redirect()->back()->with('success', 'Item deleted successfully.');
    }

    public function export()
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();

        $inventory = Inventory::where('garage_id', $garageId)->orderBy('part_name')->get();
        
        $csvHeader = ['ID', 'Part Name', 'Part Code', 'Category', 'Current Stock', 'Min Stock Level', 'Current Price', 'Location'];
        
        $sanitizeCell = function ($value) {
            if (is_string($value) && in_array(substr($value, 0, 1), ['=', '+', '-', '@', "\t", "\r"])) {
                return "'" . $value;
            }
            return $value;
        };

        $callback = function() use($inventory, $csvHeader, $sanitizeCell) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);
            
            foreach ($inventory as $item) {
                fputcsv($file, [
                    $item->id,
                    $sanitizeCell($item->part_name),
                    $sanitizeCell($item->part_code),
                    $sanitizeCell($item->category),
                    $item->current_stock,
                    $item->min_stock_level,
                    $item->current_price,
                    $sanitizeCell($item->location),
                ]);
            }
            fclose($file);
        };
        
        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory_export.csv"',
        ]);
    }

    public function bulkImport(Request $request)
    {
        $this->authorizeStaff();
        $garageId = Garage::getActiveGarageId();

        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('file');
        $fileStream = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($fileStream);

        $importedCount = 0;
        while (($row = fgetcsv($fileStream)) !== false) {
            // Basic mapping assuming standard header (Part Name, Part Code, Category, Stock, Min Stock, Price, Location)
            if (count($row) >= 6) {
                Inventory::updateOrCreate(
                    [
                        'part_code' => $row[1],
                        'garage_id' => $garageId,
                    ],
                    [
                        'part_name' => $row[0],
                        'category' => $row[2],
                        'current_stock' => (float)$row[3],
                        'min_stock_level' => (float)$row[4],
                        'current_price' => (float)$row[5],
                        'location' => $row[6] ?? null,
                        'garage_id' => $garageId,
                    ]
                );
                $importedCount++;
            }
        }
        fclose($fileStream);

        \App\Services\ActionLogService::log(
            action: 'created',
            module: 'inventory',
            description: "Bulk imported {$importedCount} inventory items from CSV spreadsheet",
            garageId: $garageId,
            details: ['imported_count' => $importedCount]
        );

        return redirect()->back()->with('success', "$importedCount items imported successfully.");
    }
}
