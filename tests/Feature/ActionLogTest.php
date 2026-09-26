<?php

namespace Tests\Feature;

use App\Models\ActionLog;
use App\Models\Garage;
use App\Models\User;
use App\Services\ActionLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionLogTest extends TestCase
{
    use RefreshDatabase;

    protected Garage $garageA;
    protected Garage $garageB;
    protected User $ownerA;
    protected User $ownerB;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->garageA = Garage::create([
            'name' => 'Speedy Motors',
            'slug' => 'speedy-motors',
            'is_active' => true,
        ]);

        $this->garageB = Garage::create([
            'name' => 'Apex Garage',
            'slug' => 'apex-garage',
            'is_active' => true,
        ]);

        $this->ownerA = User::create([
            'name' => 'Owner A',
            'email' => 'ownerA@example.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'garage_id' => $this->garageA->id,
            'is_active' => true,
        ]);

        $this->ownerB = User::create([
            'name' => 'Owner B',
            'email' => 'ownerB@example.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
            'garage_id' => $this->garageB->id,
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Master Admin',
            'email' => 'master@example.com',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_action_log_service_records_action(): void
    {
        $log = ActionLogService::log(
            action: 'created',
            module: 'inventory',
            description: 'Added test brake pads',
            garageId: $this->garageA->id,
            userId: $this->ownerA->id,
            details: ['sku' => 'BP-100']
        );

        $this->assertNotNull($log);
        $this->assertDatabaseHas('action_logs', [
            'garage_id' => $this->garageA->id,
            'action' => 'created',
            'module' => 'inventory',
            'description' => 'Added test brake pads',
        ]);
    }

    public function test_garage_owner_can_view_own_action_logs(): void
    {
        ActionLogService::log(
            action: 'created',
            module: 'job_cards',
            description: 'Garage A Job Card',
            garageId: $this->garageA->id,
            userId: $this->ownerA->id
        );

        ActionLogService::log(
            action: 'created',
            module: 'job_cards',
            description: 'Garage B Job Card',
            garageId: $this->garageB->id,
            userId: $this->ownerB->id
        );

        $response = $this->actingAs($this->ownerA)->get(route('action-logs.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('ActionLogs/Index')
                 ->has('logs.data', 1)
                 ->where('logs.data.0.description', 'Garage A Job Card')
        );
    }

    public function test_super_admin_can_view_all_action_logs_and_filter_by_garage(): void
    {
        ActionLogService::log(
            action: 'created',
            module: 'job_cards',
            description: 'Garage A Action',
            garageId: $this->garageA->id,
            userId: $this->ownerA->id
        );

        ActionLogService::log(
            action: 'created',
            module: 'purchases',
            description: 'Garage B Action',
            garageId: $this->garageB->id,
            userId: $this->ownerB->id
        );

        // Super Admin sees all
        $responseAll = $this->actingAs($this->superAdmin)->get(route('action-logs.index'));
        $responseAll->assertStatus(200);
        $responseAll->assertInertia(fn ($page) => 
            $page->component('ActionLogs/Index')
                 ->has('logs.data', 2)
                 ->where('isMasterAdmin', true)
        );

        // Filtered by Garage B
        $responseFiltered = $this->actingAs($this->superAdmin)->get(route('action-logs.index', ['garage_id' => $this->garageB->id]));
        $responseFiltered->assertStatus(200);
        $responseFiltered->assertInertia(fn ($page) => 
            $page->component('ActionLogs/Index')
                 ->has('logs.data', 1)
                 ->where('logs.data.0.description', 'Garage B Action')
        );
    }

    public function test_super_admin_can_fetch_garage_specific_logs_json(): void
    {
        ActionLogService::log(
            action: 'created',
            module: 'settings',
            description: 'Updated settings for Garage A',
            garageId: $this->garageA->id,
            userId: $this->ownerA->id
        );

        $response = $this->actingAs($this->superAdmin)->getJson(route('garages.action-logs', $this->garageA->id));
        $response->assertStatus(200);
        $response->assertJsonPath('garage.id', $this->garageA->id);
        $response->assertJsonPath('logs.data.0.description', 'Updated settings for Garage A');
    }

    public function test_garage_owner_cannot_fetch_other_garage_action_logs_json(): void
    {
        $response = $this->actingAs($this->ownerA)->getJson(route('garages.action-logs', $this->garageB->id));
        $response->assertStatus(403);
    }

    public function test_super_admin_toggling_garage_records_action_log(): void
    {
        $this->actingAs($this->superAdmin)->post(route('garages.toggle-active', $this->garageA->id));

        $this->assertDatabaseHas('action_logs', [
            'garage_id' => $this->garageA->id,
            'module' => 'garages',
            'action' => 'disabled',
        ]);
    }

    public function test_export_action_logs_csv(): void
    {
        ActionLogService::log(
            action: 'created',
            module: 'inventory',
            description: 'Engine oil 5W-30 added',
            garageId: $this->garageA->id,
            userId: $this->ownerA->id
        );

        $response = $this->actingAs($this->ownerA)->get(route('action-logs.export'));
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('Engine oil 5W-30 added', $response->streamedContent());
    }
}
