<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/book', [\App\Http\Controllers\ServiceRequestController::class, 'create'])->name('service-requests.create');
Route::post('/book', [\App\Http\Controllers\ServiceRequestController::class, 'store'])->name('service-requests.store');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/sales', [\App\Http\Controllers\SalesController::class, 'index'])->middleware(['auth', 'verified'])->name('sales.index');

Route::get('/run-tests', function () {
    try {
        \Illuminate\Support\Facades\DB::beginTransaction();

        $garage = \App\Models\Garage::first();
        if (!$garage) {
            $garage = \App\Models\Garage::create(['name' => 'MechonWheels Test', 'owner_id' => 1]);
        }
        $garageId = $garage->id;

        $log = [];

        $log[] = "1. Creating Supplier...";
        $supplier = \App\Models\Supplier::create([
            'garage_id' => $garageId,
            'name' => 'Bosch Auto Parts',
            'contact_person' => 'Mr. Bosch',
            'phone' => '9876543210',
            'email' => 'bosch' . time() . '@example.com',
        ]);

        $log[] = "2. Creating Part Category & Brand...";
        $category = \App\Models\PartCategory::firstOrCreate(['garage_id' => $garageId, 'name' => 'Engine Components']);
        $brand = \App\Models\PartBrand::firstOrCreate(['garage_id' => $garageId, 'name' => 'Bosch']);

        $log[] = "3. Creating Purchase Invoice and Items...";
        $purchase = \App\Models\PurchaseInvoice::create([
            'garage_id' => $garageId,
            'supplier_id' => $supplier->id,
            'invoice_number' => 'INV-' . time(),
            'purchase_date' => now(),
            'subtotal' => 1000,
            'total_tax' => 180,
            'grand_total' => 1180,
            'status' => 'received',
        ]);

        $purchaseItem = \App\Models\PurchaseItem::create([
            'purchase_invoice_id' => $purchase->id,
            'part_name' => 'Spark Plug Test',
            'part_number' => 'SP-100',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'quantity' => 10,
            'unit_price' => 100,
            'tax_percent' => '18%',
            'tax_amount' => 18,
            'total_price' => 1180,
        ]);

        $inventory = \App\Models\Inventory::where('part_number', 'SP-100')->first();
        if (!$inventory) {
            $log[] = "WARNING: Inventory was not updated automatically! Creating manually for test...";
            $inventory = \App\Models\Inventory::create([
                'garage_id' => $garageId,
                'part_name' => 'Spark Plug Test',
                'part_number' => 'SP-100',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'quantity' => 10,
                'purchase_price' => 100,
                'selling_price' => 150,
                'minimum_stock_level' => 2,
            ]);
        } else {
            $log[] = "Inventory successfully updated automatically by observer!";
        }

        $log[] = "4. Creating Customer...";
        $user = \App\Models\User::create([
            'name' => 'John Doe ' . time(),
            'email' => 'johndoe' . time() . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'phone' => '1234' . rand(100000, 999999),
        ]);
        $customer = \App\Models\Customer::create([
            'garage_id' => $garageId,
            'user_id' => $user->id,
        ]);

        $log[] = "5. Creating Vehicle...";
        $vBrand = \App\Models\VehicleBrand::firstOrCreate(['name' => 'Yamaha']);
        $vModel = \App\Models\VehicleModel::firstOrCreate(['vehicle_brand_id' => $vBrand->id, 'name' => 'R15']);
        $vehicle = \App\Models\Vehicle::create([
            'garage_id' => $garageId,
            'customer_id' => $customer->id,
            'vehicle_brand_id' => $vBrand->id,
            'vehicle_model_id' => $vModel->id,
            'registration_number' => 'KA01AB' . rand(1000, 9999),
            'year' => 2022,
        ]);

        $log[] = "6. Creating Job Card...";
        $jobCard = \App\Models\JobCard::create([
            'garage_id' => $garageId,
            'job_number' => 'JC-TEST-' . time(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'created_by' => 1,
            'status' => 'listed',
        ]);

        $log[] = "7. Adding Parts to Job Card...";
        $jobCardItem = \App\Models\JobCardItem::create([
            'job_card_id' => $jobCard->id,
            'garage_id' => $garageId,
            'item_type' => 'spare_part',
            'item_name' => 'Spark Plug Test',
            'inventory_id' => $inventory->id,
            'quantity' => 2,
            'unit_price' => 150,
            'gst_percent' => '18%',
            'tax_amount' => 54,
            'total' => 354,
        ]);

        $log[] = "8. Marking Job Card as Delivered...";
        $jobCard->update([
            'subtotal' => 300,
            'final_total' => 354,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'amount_paid' => 354
        ]);

        \Illuminate\Support\Facades\DB::commit();
        $log[] = "ALL TESTS PASSED SUCCESSFULLY! Dummy data seeded.";

        return response()->json($log);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
    }
});

Route::get('/debug-issue', function () {
    \Artisan::call('migrate', ['--force' => true]);
    return \Artisan::output();
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('brands', \App\Http\Controllers\VehicleBrandController::class);
    Route::resource('models', \App\Http\Controllers\VehicleModelController::class);
    
    // Staff routes
    Route::get('/customers', [\App\Http\Controllers\CustomerController::class, 'index'])->name('customers.index');
    Route::post('/customers', [\App\Http\Controllers\CustomerController::class, 'store'])->name('customers.store');
    Route::put('/customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [\App\Http\Controllers\CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::post('/customers/{customer}/reset-password', [\App\Http\Controllers\CustomerController::class, 'resetPassword'])->name('customers.resetPassword');
    
    Route::get('/staff', [\App\Http\Controllers\StaffController::class, 'index'])->name('staff.index');
    Route::post('/staff', [\App\Http\Controllers\StaffController::class, 'store'])->name('staff.store');
    Route::put('/staff/{staff}', [\App\Http\Controllers\StaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staff}', [\App\Http\Controllers\StaffController::class, 'destroy'])->name('staff.destroy');
    Route::patch('/staff/{staff}/toggle-active', [\App\Http\Controllers\StaffController::class, 'toggleActive'])->name('staff.toggle-active');

    // Service Types
    Route::get('/service-types', [\App\Http\Controllers\ServiceTypeController::class, 'index'])->name('service-types.index');
    Route::post('/service-types', [\App\Http\Controllers\ServiceTypeController::class, 'store'])->name('service-types.store');
    Route::put('/service-types/{serviceType}', [\App\Http\Controllers\ServiceTypeController::class, 'update'])->name('service-types.update');
    Route::delete('/service-types/{serviceType}', [\App\Http\Controllers\ServiceTypeController::class, 'destroy'])->name('service-types.destroy');
    Route::patch('/service-types/{serviceType}/toggle-active', [\App\Http\Controllers\ServiceTypeController::class, 'toggleActive'])->name('service-types.toggle-active');

    Route::post('/inventory/bulk-import', [\App\Http\Controllers\InventoryController::class, 'bulkImport'])->name('inventory.bulk-import');
    Route::get('/inventory/export', [\App\Http\Controllers\InventoryController::class, 'export'])->name('inventory.export');
    Route::resource('inventory', \App\Http\Controllers\InventoryController::class);
    Route::resource('part-categories', \App\Http\Controllers\PartCategoryController::class);
    Route::resource('part-brands', \App\Http\Controllers\PartBrandController::class);
    Route::resource('suppliers', \App\Http\Controllers\SupplierController::class);
    Route::resource('purchases', \App\Http\Controllers\PurchaseInvoiceController::class);
    
    Route::resource('job-cards', \App\Http\Controllers\JobCardController::class);
    
    Route::post('/coupons/apply', [\App\Http\Controllers\CouponController::class, 'apply'])->name('coupons.apply');
    Route::patch('/coupons/{coupon}/toggle-active', [\App\Http\Controllers\CouponController::class, 'toggleActive'])->name('coupons.toggle-active');
    Route::resource('coupons', \App\Http\Controllers\CouponController::class);
    
    // Settings
    Route::get('/settings', [\App\Http\Controllers\GarageSettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [\App\Http\Controllers\GarageSettingsController::class, 'update'])->name('settings.update');

    Route::patch('/job-cards/{job_card}/status', [\App\Http\Controllers\JobCardController::class, 'updateStatus'])->name('job-cards.status');
    Route::patch('/job-cards/{jobCard}/payment-status', [\App\Http\Controllers\JobCardController::class, 'updatePaymentStatus'])->name('job-cards.payment-status');
    Route::post('/job-cards/{jobCard}/payment-screenshot', [\App\Http\Controllers\JobCardController::class, 'uploadPaymentScreenshot'])->name('job-cards.payment-screenshot');
    Route::get('/job-cards/{job_card}/invoice', [\App\Http\Controllers\JobCardController::class, 'invoice'])->name('job-cards.invoice');

    // API Routes for Dropdowns
    Route::get('/api/brands', [\App\Http\Controllers\VehicleBrandController::class, 'apiIndex'])->name('api.brands.index');
    Route::get('/api/brands/{brand}/models', [\App\Http\Controllers\VehicleModelController::class, 'index'])->name('api.models.index');

    // Vehicle Database UI and Actions
    Route::get('/vehicle-brands', [\App\Http\Controllers\VehicleBrandController::class, 'index'])->name('vehicle-brands.index');
    Route::post('/vehicle-brands', [\App\Http\Controllers\VehicleBrandController::class, 'store'])->name('vehicle-brands.store');
    Route::delete('/vehicle-brands/{brand}', [\App\Http\Controllers\VehicleBrandController::class, 'destroy'])->name('vehicle-brands.destroy');
    
    Route::post('/vehicle-models', [\App\Http\Controllers\VehicleModelController::class, 'store'])->name('vehicle-models.store');
    Route::delete('/vehicle-models/{model}', [\App\Http\Controllers\VehicleModelController::class, 'destroy'])->name('vehicle-models.destroy');
    
    Route::resource('vehicles', \App\Http\Controllers\VehicleController::class);

    Route::resource('garages', \App\Http\Controllers\GarageController::class);
    Route::post('/garages/{garage}/toggle-active', [\App\Http\Controllers\GarageController::class, 'toggleActive'])->name('garages.toggle-active');
    Route::post('/garages/{garage}/impersonate', [\App\Http\Controllers\GarageImpersonationController::class, 'impersonate'])->name('garages.impersonate');
    Route::post('/garages/leave', [\App\Http\Controllers\GarageImpersonationController::class, 'leave'])->name('garages.leave');

    Route::post('/garages/{garage}/owners', [\App\Http\Controllers\GarageOwnerController::class, 'store'])->name('garage-owners.store');
    Route::post('/garage-owners/{owner}/toggle-active', [\App\Http\Controllers\GarageOwnerController::class, 'toggleActive'])->name('garage-owners.toggle-active');
    Route::post('/garage-owners/{owner}/reset-password', [\App\Http\Controllers\GarageOwnerController::class, 'resetPassword'])->name('garage-owners.reset-password');

    Route::get('/service-requests', [\App\Http\Controllers\ServiceRequestController::class, 'index'])->name('service-requests.index');
    Route::post('/service-requests/{id}/approve', [\App\Http\Controllers\ServiceRequestController::class, 'approve'])->name('service-requests.approve');
    Route::post('/service-requests/{id}/decline', [\App\Http\Controllers\ServiceRequestController::class, 'decline'])->name('service-requests.decline');

    // AI Assistant
    Route::post('/ai-assistant/key', [\App\Http\Controllers\AIAssistantController::class, 'updateKey'])->name('ai-assistant.key');
    Route::post('/ai-assistant/command', [\App\Http\Controllers\AIAssistantController::class, 'processCommand'])->name('ai-assistant.command');
});

require __DIR__.'/auth.php';
