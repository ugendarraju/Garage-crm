<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InstallController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/install', [InstallController::class, 'index'])->name('install.index');
Route::post('/install', [InstallController::class, 'setup'])->name('install.setup');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/book/{slug?}', [\App\Http\Controllers\ServiceRequestController::class, 'create'])->name('service-requests.create');
Route::post('/book', [\App\Http\Controllers\ServiceRequestController::class, 'store'])->name('service-requests.store');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/sales', [\App\Http\Controllers\SalesController::class, 'index'])->middleware(['auth', 'verified'])->name('sales.index');



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

    // Super Admin CRM Settings
    Route::get('/admin/settings', [\App\Http\Controllers\SuperAdmin\SystemSettingsController::class, 'edit'])->name('admin.settings.index');
    Route::post('/admin/settings', [\App\Http\Controllers\SuperAdmin\SystemSettingsController::class, 'update'])->name('admin.settings.update');

    // Interactive User Guide & Help Center
    Route::get('/user-guide', [\App\Http\Controllers\UserGuideController::class, 'index'])->name('user-guide');
});

require __DIR__.'/auth.php';
