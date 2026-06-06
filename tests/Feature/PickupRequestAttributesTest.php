<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\PricingRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PickupRequestAttributesTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $category;
    protected $attribute;
    protected $city;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Roles
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Disable proof images requirement
        \App\Models\AppSetting::set('scrap_proof_images_required', false);

        // Create city
        $this->city = \App\Models\City::factory()->create();

        // Create customer
        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');

        // Create category
        $this->category = Category::create([
            'name' => ['en' => 'Laptops'],
            'slug' => 'laptops',
            'status' => true
        ]);

        // Create attribute
        $this->attribute = Attribute::create([
            'name' => ['en' => 'Condition'],
            'code' => 'condition',
            'slug' => 'condition',
            'type' => 'select',
            'status' => true
        ]);

        // Create attribute options
        AttributeOption::create([
            'attribute_id' => $this->attribute->id,
            'value' => ['en' => 'Working'],
            'sort_order' => 0
        ]);

        AttributeOption::create([
            'attribute_id' => $this->attribute->id,
            'value' => ['en' => 'Non-Working'],
            'sort_order' => 1
        ]);

        // Link attribute to category as required
        $this->category->attributes()->attach($this->attribute->id, ['is_required' => true]);

        // Create pricing rule
        PricingRule::create([
            'category_id' => $this->category->id,
            'base_price' => 500,
            'currency' => 'INR',
            'status' => true
        ]);
    }

    public function test_customer_can_create_pickup_request_with_attributes()
    {
        $response = $this->actingAs($this->customer)->postJson('/api/pickup-request', [
            'address' => '123 Test Street, Test City',
            'city_id' => $this->city->id,
            'payout_method' => 'cash',
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'category_id' => $this->category->id,
                    'quantity' => 2,
                    'weight' => null,
                    'attributes' => [
                        [
                            'attribute_id' => $this->attribute->id,
                            'value' => 'Working'
                        ]
                    ]
                ]
            ]
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        // Verify attribute was saved
        $this->assertDatabaseHas('pickup_request_attributes', [
            'attribute_id' => $this->attribute->id,
        ]);
    }

    public function test_pickup_request_fails_without_required_attributes()
    {
        $response = $this->actingAs($this->customer)->postJson('/api/pickup-request', [
            'address' => '123 Test Street, Test City',
            'city_id' => $this->city->id,
            'payout_method' => 'cash',
            'latitude' => 28.7041,
            'longitude' => 77.1025,
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'items' => [
                [
                    'category_id' => $this->category->id,
                    'quantity' => 2,
                    'weight' => null,
                    // Missing required attributes
                ]
            ]
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 400);
    }
}
