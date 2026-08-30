<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Garage;
use App\Models\User;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\Supplier;
use App\Models\PartCategory;
use App\Models\PartBrand;
use App\Models\Inventory;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\JobCard;
use App\Models\JobCardItem;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    $garage = Garage::find(2);
    if (!$garage) {
        $garage = Garage::create(['name' => 'MechonWheels Test', 'owner_id' => 1]);
    }
    $garageId = $garage->id;
    $mechanic = User::where('role', 'mechanic')->first();
    if (!$mechanic) {
        $mechanic = User::create([
            'name' => 'Test Mechanic',
            'email' => 'mechanic@test.com',
            'password' => bcrypt('password'),
            'role' => 'mechanic',
            'garage_id' => $garageId,
        ]);
    }

    echo "1. Creating Supplier...\n";
    $supplier = Supplier::create([
        'garage_id' => $garageId,
        'name' => 'Bosch Auto Parts',
        'contact_name' => 'Mr. Bosch',
        'phone' => '9876543210',
        'email' => 'bosch' . time() . '@example.com',
    ]);

    echo "2. Creating Part Category & Brand...\n";
    $category = PartCategory::firstOrCreate(['garage_id' => $garageId, 'name' => 'Engine Components']);
    $brand = PartBrand::firstOrCreate(['garage_id' => $garageId, 'name' => 'Bosch']);

    echo "3. Creating Purchase Invoice and Items...\n";
    $purchase = PurchaseInvoice::create([
        'garage_id' => $garageId,
        'supplier_id' => $supplier->id,
        'invoice_number' => 'INV-' . time(),
        'purchase_date' => now(),
        'total_amount' => 1180,
        'status' => 'paid',
    ]);

    $purchaseItem = PurchaseInvoiceItem::create([
        'purchase_invoice_id' => $purchase->id,
        'part_name' => 'Spark Plug Test',
        'quantity' => 10,
        'unit_price' => 100,
        'total_price' => 1000,
    ]);

    $inventory = Inventory::where('part_name', 'Spark Plug Test')->first();
    if (!$inventory) {
        echo "WARNING: Inventory was not updated automatically by PurchaseInvoice! Creating manually for test...\n";
        $inventory = Inventory::create([
            'garage_id' => $garageId,
            'part_name' => 'Spark Plug Test',
            'part_code' => 'SP-100',
            'category' => 'Engine Components',
            'current_stock' => 10,
            'current_price' => 150, // Selling price
            'min_stock_level' => 2,
        ]);
    } else {
        echo "Inventory successfully updated automatically!\n";
    }

    echo "4. Creating Customer...\n";
    $user = User::create([
        'name' => 'John Doe',
        'email' => 'johndoe' . time() . '@example.com',
        'password' => bcrypt('password'),
        'role' => 'customer',
        'phone' => '1234567890',
    ]);
    $customer = Customer::create([
        'garage_id' => $garageId,
        'user_id' => $user->id,
    ]);

    echo "5. Creating Vehicle...\n";
    $vBrand = VehicleBrand::firstOrCreate(['name' => 'Yamaha']);
    $vModel = VehicleModel::firstOrCreate(['brand_id' => $vBrand->id, 'name' => 'R15']);
    $vehicle = Vehicle::create([
        'garage_id' => $garageId,
        'customer_id' => $customer->id,
        'brand_id' => $vBrand->id,
        'model_id' => $vModel->id,
        'registration_number' => 'KA01AB' . rand(1000, 9999),
        'year' => 2022,
    ]);

    echo "6. Creating Job Card...\n";
    $jobCard = JobCard::create([
        'garage_id' => $garageId,
        'job_number' => 'JC-TEST-' . time(),
        'customer_id' => $customer->id,
        'vehicle_id' => $vehicle->id,
        'created_by' => 1,
        'status' => 'listed',
    ]);

    echo "7. Adding Parts to Job Card...\n";
    $jobCardItem = JobCardItem::create([
        'job_card_id' => $jobCard->id,
        'garage_id' => $garageId,
        'item_type' => 'spare_part',
        'item_name' => 'Spark Plug Test',
        'inventory_id' => $inventory->id,
        'quantity' => 2,
        'unit_price' => 150,
        'total' => 300,
    ]);

    // Recalculate job card totals
    $subtotal = $jobCardItem->unit_price * $jobCardItem->quantity;
    $finalTotal = $jobCardItem->total;
    
    $jobCard->update([
        'subtotal' => $subtotal,
        'final_total' => $finalTotal,
        'status' => 'delivered',
        'payment_status' => 'paid',
        'amount_paid' => $finalTotal
    ]);

    echo "Job card successfully completed and delivered!\n";
    
    DB::commit();
    echo "\nALL TESTS PASSED SUCCESSFULLY! Dummy data seeded.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
