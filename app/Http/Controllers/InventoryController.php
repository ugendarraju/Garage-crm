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

class InventoryController extends Controller
{
    public function index()
    {
        $inventory = Inventory::orderBy('part_name')->get();
        $categories = PartCategory::orderBy('name')->get();
        $brands = PartBrand::orderBy('name')->get();
        
        return Inertia::render('Inventory/Index', [
            'inventory' => $inventory,
            'categories' => $categories,
            'brands' => $brands,
        ]);
    }

    public function create()
    {
        return Inertia::render('Inventory/Create');
    }

    public function store(StoreInventoryRequest $request)
    {
        $validated = $request->validate([
            'part_name' => 'required|string|max:255',
            'part_code' => 'nullable|string|max:100|unique:inventories,part_code',
            'category' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'current_stock' => 'required|integer|min:0',
            'min_stock_level' => 'required|integer|min:0',
            'current_price' => 'required|numeric|min:0',
            'gst_rate' => 'required|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
        ]);

        Inventory::create($validated);

        return redirect()->route('inventory.index')->with('success', 'Item added to inventory.');
    }

    public function show(Inventory $inventory)
    {
        return Inertia::render('Inventory/Show', [
            'item' => $inventory
        ]);
    }

    public function edit(Inventory $inventory)
    {
        return Inertia::render('Inventory/Edit', [
            'item' => $inventory
        ]);
    }

    public function update(UpdateInventoryRequest $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'part_name' => 'required|string|max:255',
            'part_code' => 'nullable|string|max:100|unique:inventories,part_code,'.$inventory->id,
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

        return redirect()->route('inventory.index')->with('success', 'Inventory item updated.');
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        return redirect()->back()->with('success', 'Item deleted successfully.');
    }

    public function export()
    {
        $inventory = Inventory::all();
        
        $csvHeader = ['ID', 'Part Name', 'Part Code', 'Category', 'Current Stock', 'Min Stock Level', 'Current Price', 'Location'];
        
        $callback = function() use($inventory, $csvHeader) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $csvHeader);
            
            foreach ($inventory as $item) {
                fputcsv($file, [
                    $item->id,
                    $item->part_name,
                    $item->part_code,
                    $item->category,
                    $item->current_stock,
                    $item->min_stock_level,
                    $item->current_price,
                    $item->location,
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
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:2048'
        ]);

        $file = $request->file('file');
        $fileStream = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($fileStream);

        $importedCount = 0;
        while (($row = fgetcsv($fileStream)) !== false) {
            // Basic mapping assuming standard header (Part Name, Part Code, Category, Stock, Min Stock, Price, Location)
            // Adjust indexes based on actual CSV format expected
            if (count($row) >= 6) {
                Inventory::updateOrCreate(
                    ['part_code' => $row[1]], // Assuming Part Code is unique
                    [
                        'part_name' => $row[0],
                        'category' => $row[2],
                        'current_stock' => (float)$row[3],
                        'min_stock_level' => (float)$row[4],
                        'current_price' => (float)$row[5],
                        'location' => $row[6] ?? null,
                    ]
                );
                $importedCount++;
            }
        }
        fclose($fileStream);

        return redirect()->back()->with('success', "$importedCount items imported successfully.");
    }
}
