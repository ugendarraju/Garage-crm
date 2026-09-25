<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Garage;
use App\Models\Inventory;
use App\Models\JobCard;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoShopFunctionalTest extends TestCase
{
    use RefreshDatabase;

    protected User $demoOwner;
    protected Garage $demoGarage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->demoGarage = Garage::create([
            'name' => 'Demo Auto Shop',
            'slug' => 'demo-auto-shop',
            'phone' => '9876543210',
            'email' => 'owner@demoauto.com',
            'address' => '123 Demo St',
            'city' => 'Bangalore',
            'state' => 'Karnataka',
            'pincode' => '560001',
            'is_active' => true,
        ]);

        $this->demoOwner = User::create([
            'name' => 'Demo Owner',
            'email' => 'owner@demoauto.com',
            'phone' => '9876543210',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'garage_id' => $this->demoGarage->id,
            'is_active' => true,
        ]);
    }

    public function test_user_guide_loads_successfully(): void
    {
        $response = $this->actingAs($this->demoOwner)->get(route('user-guide'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('UserGuide/Index'));
    }

    public function test_dashboard_loads(): void
    {
        $response = $this->actingAs($this->demoOwner)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Dashboard'));
    }

    public function test_job_cards_lifecycle_and_invoice(): void
    {
        $response = $this->actingAs($this->demoOwner)->get(route('job-cards.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->demoOwner)->get(route('job-cards.create'));
        $response->assertStatus(200);

        $customerUser = User::create([
            'name' => 'John Customer',
            'email' => 'john.cust@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $customer = Customer::create([
            'garage_id' => $this->demoGarage->id,
            'user_id' => $customerUser->id,
        ]);
        $brand = \App\Models\VehicleBrand::create(['name' => 'Honda']);
        $model = \App\Models\VehicleModel::create(['brand_id' => $brand->id, 'name' => 'Civic']);
        $vehicle = Vehicle::create([
            'garage_id' => $this->demoGarage->id,
            'customer_id' => $customer->id,
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'registration_number' => 'KA02XY9999',
        ]);

        $jobCard = JobCard::create([
            'garage_id' => $this->demoGarage->id,
            'job_number' => 'JC-DEMO-001',
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'created_by' => $this->demoOwner->id,
            'status' => 'listed',
            'payment_status' => 'pending',
            'estimated_cost' => 1500,
            'total_amount' => 1500,
        ]);

        // Status update
        $response = $this->actingAs($this->demoOwner)->patch(route('job-cards.status', $jobCard->id), [
            'status' => 'working',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals('working', $jobCard->fresh()->status);

        // Payment status update
        $response = $this->actingAs($this->demoOwner)->patch(route('job-cards.payment-status', $jobCard->id), [
            'payment_status' => 'paid',
            'payment_method' => 'upi',
        ]);
        $response->assertSessionHasNoErrors();
        $this->assertEquals('paid', $jobCard->fresh()->payment_status);

        // Invoice viewing
        $response = $this->actingAs($this->demoOwner)->get(route('job-cards.invoice', $jobCard->id));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('JobCards/Invoice'));
    }

    public function test_service_requests_index(): void
    {
        $response = $this->actingAs($this->demoOwner)->get(route('service-requests.index'));
        $response->assertStatus(200);
    }

    public function test_customers_and_vehicles_flow(): void
    {
        $response = $this->actingAs($this->demoOwner)->get(route('customers.index'));
        $response->assertStatus(200);

        $customerUser = User::create([
            'name' => 'Ravi Kumar',
            'email' => 'ravi@example.com',
            'phone' => '9876500001',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'garage_id' => $this->demoGarage->id,
            'user_id' => $customerUser->id,
            'address' => 'Indiranagar, Bangalore',
        ]);

        $brand = \App\Models\VehicleBrand::create(['name' => 'Hyundai']);
        $model = \App\Models\VehicleModel::create(['brand_id' => $brand->id, 'name' => 'Creta']);

        $vehicle = Vehicle::create([
            'garage_id' => $this->demoGarage->id,
            'customer_id' => $customer->id,
            'brand_id' => $brand->id,
            'model_id' => $model->id,
            'registration_number' => 'KA01AB1234',
            'year' => 2021,
        ]);

        // Test vehicle detail endpoint
        $response = $this->actingAs($this->demoOwner)->get(route('vehicles.show', $vehicle->id));
        $response->assertStatus(200);
    }

    public function test_inventory_and_purchases(): void
    {
        $response = $this->actingAs($this->demoOwner)->get(route('inventory.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->demoOwner)->get(route('purchases.index'));
        $response->assertStatus(200);

        $item = Inventory::create([
            'garage_id' => $this->demoGarage->id,
            'part_name' => 'Engine Oil 5W30',
            'part_code' => 'OIL-5W30-01',
            'current_stock' => 25,
            'min_stock_level' => 5,
            'unit' => 'Litre',
            'current_price' => 650.00,
            'gst_rate' => 18.00,
        ]);

        $this->assertDatabaseHas('inventories', [
            'id' => $item->id,
            'garage_id' => $this->demoGarage->id,
            'part_name' => 'Engine Oil 5W30',
        ]);
    }

    public function test_expenses_and_sales(): void
    {
        $response = $this->actingAs($this->demoOwner)->get(route('expenses.index'));
        $response->assertStatus(200);

        // Test creating an expense with an attachment
        Storage::fake('public');
        $file = UploadedFile::fake()->image('bill_receipt.jpg');

        $response = $this->actingAs($this->demoOwner)->post(route('expenses.store'), [
            'title' => 'Workshop Electricity Bill',
            'amount' => 1250.00,
            'category' => 'utilities',
            'expense_date' => now()->toDateString(),
            'notes' => 'Paid via UPI',
            'receipt' => $file,
        ]);
        $response->assertSessionHasNoErrors();

        $response = $this->actingAs($this->demoOwner)->get(route('sales.index'));
        $response->assertStatus(200);
    }

    public function test_service_reminders_and_staff(): void
    {
        $response = $this->actingAs($this->demoOwner)->get(route('service-reminders.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->demoOwner)->get(route('staff.index'));
        $response->assertStatus(200);
    }

    public function test_settings_page(): void
    {
        $response = $this->actingAs($this->demoOwner)->get(route('settings.edit'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->demoOwner)->post(route('settings.update'), [
            'name' => 'Demo Auto Shop Updated',
            'phone' => '9876543210',
            'email' => 'owner@demoauto.com',
            'address' => '123 Demo St',
            'city' => 'Bangalore',
            'state' => 'Karnataka',
            'pincode' => '560001',
        ]);
        $response->assertSessionHasNoErrors();
    }

    public function test_public_booking_page_for_demo_shop(): void
    {
        $response = $this->get('/book/' . $this->demoGarage->slug);
        $response->assertStatus(200);
    }

    public function test_super_admin_guide_and_impersonation(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@garage.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'garage_id' => null,
            'is_active' => true,
        ]);

        // Super Admin viewing user guide in master view
        $response = $this->actingAs($superAdmin)->get(route('user-guide'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('UserGuide/Index'));

        // Super Admin impersonating Demo Auto Shop
        $response = $this->actingAs($superAdmin)->post(route('garages.impersonate', $this->demoGarage->id));
        $response->assertRedirect(route('dashboard'));

        // Verify active garage session
        $this->assertEquals($this->demoGarage->id, session('active_garage_id'));

        // Super Admin leaving impersonation
        $response = $this->actingAs($superAdmin)->post(route('garages.leave'));
        $response->assertRedirect(route('garages.index'));
        $this->assertNull(session('active_garage_id'));
    }
}
