<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\PickupRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class ApiEndpointsTest extends TestCase
{
    // usage of RefreshDatabase to reset db state for tests
    // However, since we want to test against the *seeded* data or persistent data, 
    // we might avoid RefreshDatabase if we want to preserve the "running" state.
    // usage of RefreshDatabase is best practice for reproducible tests.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed the database to ensure roles and permissions exist
        $this->seed();
    }

    public function test_auth_flow()
    {
        // Test Customer login OTP flow
        $response = $this->postJson('/api/auth/login/send-otp', [
            'phone' => '9999999999'
        ]);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $response = $this->postJson('/api/auth/login/verify-otp', [
            'phone' => '9999999999',
            'otp' => '123456',
            'device_name' => 'Test Device'
        ]);
        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        // Test Protected Role OTP flow
        $response = $this->postJson('/api/auth/send-otp', [
            'phone' => '6666666666',
            'role' => 'pickup_boy'
        ]);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_categories_route()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user)->getJson('/api/categories');
        $response->assertStatus(200);
    }

    public function test_customer_can_create_pickup_request()
    {
        // Create a customer user
        $user = User::factory()->create();
        $user->assignRole('customer');

        // Create a category with Pricing Rule
        $slug = 'e-waste-' . uniqid();
        $category = Category::create(['name' => ['en' => 'E-Waste'], 'slug' => $slug]);
        \App\Models\PricingRule::create(['category_id' => $category->id, 'base_price' => 10]);

        $city = \App\Models\City::factory()->create();
        \App\Models\AppSetting::set('scrap_proof_images_required', false);

        // Act as the user
        $response = $this->actingAs($user)->postJson('/api/pickup-request', [
            'address' => '123 Test St',
            'city_id' => $city->id,
            'payout_method' => 'cash',
            'scheduled_at' => now()->addDays(2)->toDateTimeString(),
            'items' => [
                ['category_id' => $category->id, 'weight' => 5, 'quantity' => 2]
            ]
        ]);

        $response->assertStatus(201);
    }

    public function test_user_can_delete_their_account_via_api()
    {
        $user = User::factory()->create();
        $user->assignRole('customer');

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/auth/profile');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', trans('profile.deleted'));

        $this->assertNull($user->fresh());
    }

    public function test_admin_can_assign_pickup()
    {
        // Setup Users
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $pickupBoy = User::factory()->create();
        $pickupBoy->assignRole('pickup_boy');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        // Setup Data
        $slug = 'scrap-' . uniqid();
        $category = Category::create(['name' => ['en' => 'Scrap'], 'slug' => $slug]);
        \App\Models\PricingRule::create(['category_id' => $category->id, 'base_price' => 5]);
        $pickup = PickupRequest::create([
            'customer_id' => $customer->id,
            'address' => 'Test Address',
            'scheduled_at' => now()->addDays(2),
            'status' => 'pending',
            'reference_id' => 'REF123'
        ]);

        // Act as Admin
        $response = $this->actingAs($admin)->postJson("/api/admin/pickups/{$pickup->id}/assign", [
            'pickup_boy_id' => $pickupBoy->id
        ]);

        $response->assertStatus(200);
        $this->assertEquals('assigned', $pickup->fresh()->status);
    }
}
