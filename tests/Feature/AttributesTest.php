<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Attribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class AttributesTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed Roles
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_create_attribute()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/admin/attributes', [
            'name' => ['en' => 'Color'],
            'code' => 'color',
            'type' => 'select',
            'options' => ['Red', 'Green', 'Blue'],
            'is_required' => true
        ]);
        file_put_contents('test_response.txt', json_encode($response->json(), JSON_PRETTY_PRINT));
        // dd($response->json());

        $response->assertJsonPath('data.code', 'color')
            ->assertJsonPath('data.options.0.value.en', 'Red');

        $this->assertDatabaseHas('attributes', ['code' => 'color']);
    }

    public function test_admin_can_assign_attribute_to_category()
    {
        // 1. Create Category
        $category = Category::create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics']);

        // 2. Create Attribute
        $attribute = Attribute::create([
            'name' => ['en' => 'Brand'],
            'code' => 'brand',
            'slug' => 'brand',
            'type' => 'text',
            'status' => true
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->postJson("/api/admin/attributes/{$attribute->id}/assign", [
            'category_id' => $category->id,
            'is_required' => true
        ]);

        $response->assertStatus(200);

        // 4. Verify Pivot
        $this->assertDatabaseHas('category_attributes', [
            'category_id' => $category->id,
            'attribute_id' => $attribute->id,
            'is_required' => 1
        ]);
    }

    public function test_admin_can_update_attribute_and_sync_options_without_deleting_pricing_rules()
    {
        // 1. Create Category
        $category = Category::create(['name' => ['en' => 'Air Conditioner'], 'slug' => 'air-conditioner']);

        // 2. Create Attribute with select type
        $attribute = Attribute::create([
            'name' => ['en' => 'Cooling Capacity', 'hi' => 'Cooling Capacity'],
            'code' => 'AC',
            'slug' => 'cooling-capacity',
            'type' => 'select',
            'status' => true
        ]);

        // 3. Create Option
        $option1 = $attribute->options()->create([
            'value' => ['en' => '0.8-1 Ton', 'hi' => '0.8-1 Ton'],
        ]);

        $option2 = $attribute->options()->create([
            'value' => ['en' => '1.5 Ton', 'hi' => '1.5 Ton'],
        ]);

        // Link category attribute
        $category->attributes()->attach($attribute->id);

        // 4. Create pricing rule for option 1 and option 2
        $pricingRule1 = \App\Models\PricingRule::create([
            'category_id' => $category->id,
            'attribute_option_id' => $option1->id,
            'base_price' => 0,
            'adjustment_type' => 'percentage',
            'adjustment_value' => 10,
            'pricing_type' => 'per_piece',
        ]);

        $pricingRule2 = \App\Models\PricingRule::create([
            'category_id' => $category->id,
            'attribute_option_id' => $option2->id,
            'base_price' => 0,
            'adjustment_type' => 'percentage',
            'adjustment_value' => 20,
            'pricing_type' => 'per_piece',
        ]);

        // 5. Update Attribute via admin web route (using the Web/Inertia controller)
        $response = $this->actingAs($this->admin)->put("/attributes/{$attribute->id}", [
            'name' => ['en' => 'Cooling Capacity Updated', 'hi' => 'Cooling Capacity Hindi'],
            'code' => 'AC',
            'type' => 'select',
            'options' => ['0.8-1 Ton', '1.5 Ton', '2 Ton'], // Keeping 0.8-1 Ton and 1.5 Ton, adding 2 Ton
            'categories' => [$category->id],
        ]);

        $response->assertRedirect();

        // 6. Verify that option1 and option2 were preserved (IDs did not change)
        $this->assertDatabaseHas('attribute_options', [
            'id' => $option1->id,
            'value->en' => '0.8-1 Ton'
        ]);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option2->id,
            'value->en' => '1.5 Ton'
        ]);

        // 7. Verify new option was added
        $this->assertDatabaseHas('attribute_options', [
            'value->en' => '2 Ton',
            'attribute_id' => $attribute->id
        ]);

        // 8. Verify that the pricing rules for option1 and option2 STILL EXIST and were NOT deleted!
        $this->assertDatabaseHas('pricing_rules', [
            'id' => $pricingRule1->id,
            'attribute_option_id' => $option1->id,
            'adjustment_value' => 10
        ]);

        $this->assertDatabaseHas('pricing_rules', [
            'id' => $pricingRule2->id,
            'attribute_option_id' => $option2->id,
            'adjustment_value' => 20
        ]);
    }
}
