<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Garage;
use App\Models\User;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\CustomerInvitationMail;
use Tests\TestCase;

class CustomerInviteAndValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Services\DatabaseSchemaAutoFixService::ensureSchemaIsUpToDate();
    }

    private function createGarageWithOwner(): array
    {
        $garage = Garage::create([
            'name' => 'Apex Motors',
            'email' => 'apex@example.com',
            'phone' => '9988776655',
            'address' => 'Industrial Area',
            'status' => 'active',
        ]);

        $owner = User::create([
            'name' => 'Apex Owner',
            'email' => 'owner' . uniqid() . '@apex.com',
            'phone' => '9988' . rand(100000, 999999),
            'password' => bcrypt('password123'),
            'role' => 'owner',
            'garage_id' => $garage->id,
        ]);

        return [$garage, $owner];
    }

    public function test_phone_and_whatsapp_cannot_be_arbitrary_text(): void
    {
        [$garage, $owner] = $this->createGarageWithOwner();

        $response = $this->actingAs($owner)
            ->withSession(['active_garage_id' => $garage->id])
            ->post(route('customers.store'), [
                'name' => 'Invalid Phone User',
                'phone' => 'fgfgg',
                'email' => 'valid@gmail.com',
                'whatsapp_number' => 'ssdas',
            ]);

        $response->assertSessionHasErrors(['phone', 'whatsapp_number']);
    }

    public function test_customer_creation_generates_temporary_password_and_sets_must_change_password(): void
    {
        Mail::fake();
        [$garage, $owner] = $this->createGarageWithOwner();

        $customerEmail = 'invitee' . uniqid() . '@gmail.com';
        $customerPhone = '9876' . rand(100000, 999999);

        $response = $this->actingAs($owner)
            ->withSession(['active_garage_id' => $garage->id])
            ->post(route('customers.store'), [
                'name' => 'Valid Customer',
                'phone' => $customerPhone,
                'email' => $customerEmail,
                'whatsapp_number' => $customerPhone,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('new_customer_credentials');

        $credentials = session('new_customer_credentials');
        $this->assertNotEmpty($credentials['temporary_password']);
        $this->assertTrue(str_starts_with($credentials['temporary_password'], 'PC#'));

        $user = User::where('phone', $customerPhone)->first();
        $this->assertNotNull($user);
        $this->assertTrue((bool)$user->must_change_password);
        $this->assertTrue(Hash::check($credentials['temporary_password'], $user->password));

        Mail::assertSent(CustomerInvitationMail::class, function ($mail) use ($customerEmail) {
            return $mail->hasTo($customerEmail);
        });
    }

    public function test_customer_with_must_change_password_is_redirected_to_change_password(): void
    {
        [$garage, $owner] = $this->createGarageWithOwner();

        $customerUser = User::create([
            'name' => 'First Time Customer',
            'email' => 'firsttime' . uniqid() . '@example.com',
            'phone' => '9123' . rand(100000, 999999),
            'password' => bcrypt('TempPass#123'),
            'role' => 'customer',
            'must_change_password' => true,
        ]);

        Customer::create([
            'garage_id' => $garage->id,
            'user_id' => $customerUser->id,
        ]);

        // Attempting to visit dashboard should redirect to change-password
        $response = $this->actingAs($customerUser)->get(route('dashboard'));
        $response->assertRedirect(route('password.force_change'));

        // Customer submits new permanent password
        $changeResponse = $this->actingAs($customerUser)->post(route('password.force_update'), [
            'current_password' => 'TempPass#123',
            'password' => 'NewSecurePass@2026',
            'password_confirmation' => 'NewSecurePass@2026',
        ]);

        $changeResponse->assertRedirect(route('dashboard'));

        $customerUser->refresh();
        $this->assertFalse((bool)$customerUser->must_change_password);
        $this->assertTrue(Hash::check('NewSecurePass@2026', $customerUser->password));

        // Now dashboard access succeeds directly
        $dashResponse = $this->actingAs($customerUser)->get(route('dashboard'));
        $dashResponse->assertOk();
    }

    public function test_service_request_submission_rejects_invalid_phone(): void
    {
        [$garage, $owner] = $this->createGarageWithOwner();

        $response = $this->post(route('service-requests.store'), [
            'garage_id' => $garage->id,
            'customer_name' => 'John Doe',
            'email' => 'john' . uniqid() . '@example.com',
            'phone' => 'abcde123invalid',
            'vehicle_brand' => 'Honda',
            'vehicle_model' => 'Civic',
            'vehicle_year' => '2022',
            'issues' => [
                ['description' => 'Brake noise'],
            ],
        ]);

        $response->assertSessionHasErrors(['phone']);
    }

    public function test_service_request_approval_generates_temp_password_and_invites_customer(): void
    {
        Mail::fake();
        [$garage, $owner] = $this->createGarageWithOwner();

        $customerEmail = 'sr_customer' . uniqid() . '@example.com';
        $customerPhone = '9812' . rand(100000, 999999);

        // Submit service request
        $serviceRequest = \App\Models\ServiceRequest::create([
            'garage_id' => $garage->id,
            'customer_name' => 'Sara Connor',
            'email' => $customerEmail,
            'phone' => $customerPhone,
            'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Corolla',
            'vehicle_year' => '2021',
            'issues' => json_encode([['description' => 'Engine oil change', 'image_path' => null]]),
            'status' => 'pending',
        ]);

        // Garage owner approves request
        $response = $this->actingAs($owner)
            ->withSession(['active_garage_id' => $garage->id])
            ->post(route('service-requests.approve', $serviceRequest->id));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('new_customer_credentials');

        $credentials = session('new_customer_credentials');
        $this->assertNotEmpty($credentials['temporary_password']);
        $this->assertTrue(str_starts_with($credentials['temporary_password'], 'PC#'));

        $user = User::where('email', $customerEmail)->first();
        $this->assertNotNull($user);
        $this->assertTrue((bool)$user->must_change_password);
        $this->assertTrue(Hash::check($credentials['temporary_password'], $user->password));

        Mail::assertSent(CustomerInvitationMail::class, function ($mail) use ($customerEmail) {
            return $mail->hasTo($customerEmail);
        });

        $serviceRequest->refresh();
        $this->assertEquals('approved', $serviceRequest->status);
    }
}
