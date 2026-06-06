<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryType;
use App\Models\City;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CorporateBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_corporate_options_only_return_enabled_category_types(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'customer']));
        Sanctum::actingAs($user);

        CategoryType::create([
            'name' => ['en' => 'E-Waste-Test', 'hi' => 'E-Waste-Test'],
            'slug' => 'e-waste-test',
            'status' => true,
            'show_in_corporate_booking' => true,
        ]);

        CategoryType::create([
            'name' => ['en' => 'Hazardous-Waste-Test', 'hi' => 'Hazardous-Waste-Test'],
            'slug' => 'hazardous-waste-test',
            'status' => true,
            'show_in_corporate_booking' => false,
        ]);

        $response = $this->getJson('/api/corporate-bookings/options');

        $response->assertOk();
        $response->assertJsonFragment(['name' => 'E-Waste-Test']);
        $response->assertJsonMissing(['name' => 'Hazardous-Waste-Test']);
    }

    public function test_corporate_booking_can_be_created_from_direct_item_quantities_without_detail_flow(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::firstOrCreate(['name' => 'customer']));
        Sanctum::actingAs($user);

        $city = City::factory()->create();
        $warehouse = Warehouse::factory()->create([
            'city_id' => $city->id,
            'accepts_corporate' => true,
            'status' => true,
        ]);

        \App\Models\AppSetting::set('corporate_main_warehouse_id', $warehouse->id);

        $type = CategoryType::create([
            'name' => ['en' => 'E-Waste-Test-2', 'hi' => 'E-Waste-Test-2'],
            'slug' => 'e-waste-test-2',
            'status' => true,
            'show_in_corporate_booking' => true,
        ]);

        $category = Category::create([
            'name' => ['en' => 'Air Conditioner', 'hi' => 'Air Conditioner'],
            'slug' => 'air-conditioner-test',
            'category_type_id' => $type->id,
            'parent_id' => null,
            'status' => true,
        ]);

        $response = $this->postJson('/api/corporate-bookings', [
            'address' => 'Corporate Park, Test City',
            'city_id' => $city->id,
            'scheduled_at' => now()->addDay()->toDateTimeString(),
            'company_name' => 'Acme Pvt Ltd',
            'contact_name' => 'Test User',
            'contact_mobile' => '9876543210',
            'contact_email' => 'corp@example.com',
            'meeting_type' => 'in_person',
            'items' => [
                [
                    'category_id' => $category->id,
                    'quantity' => 10,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.metadata.corporate_categories.0', 'E-Waste-Test-2');
        $response->assertJsonPath('data.metadata.corporate_category_items.0.corporate_category', 'Air Conditioner');
        $response->assertJsonPath('data.metadata.corporate_category_items.0.quantity', 10);
        $response->assertJsonPath('data.metadata.corporate_category_items.0.unit', 'qns');
        $response->assertJsonPath('data.estimated_amount', null);
        $this->assertDatabaseHas('pickup_requests', [
            'warehouse_id' => $warehouse->id,
            'request_type' => 'corporate',
        ]);
    }
}
