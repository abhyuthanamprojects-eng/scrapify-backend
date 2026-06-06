<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\CategoryAttribute;
use App\Models\PricingRule;
use Illuminate\Support\Str;

class HomeApplianceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Define Attributes and their Options
        $attributesConfig = [
            'Brand' => [
                'type' => 'select',
                'options' => ['Voltas', 'LG', 'Samsung', 'Daikin', 'Blue Star', 'IFB', 'Whirlpool', 'Godrej', 'Sony', 'Mi', 'Panasonic', 'Haier', 'Others']
            ],
            'Capacity (Tons)' => [
                'type' => 'select',
                'options' => ['1 Ton', '1.5 Ton', '2 Ton', '2 Ton+']
            ],
            'Body Type' => [
                'type' => 'select',
                'options' => ['Metal Body', 'Plastic Body']
            ],
            'Microwave Type' => [
                'type' => 'select',
                'options' => ['Solo', 'Grill', 'Convection']
            ],
            'Capacity (Liters - Microwave)' => [
                'type' => 'select',
                'options' => ['20L', '25L', '30L', '35L+']
            ],
            'Machine Type' => [
                'type' => 'select',
                'options' => ['Top Load', 'Front Load', 'Semi-Automatic']
            ],
            'Capacity (kg)' => [
                'type' => 'select',
                'options' => ['6 kg', '7 kg', '8 kg', '9 kg+']
            ],
            'Display Type' => [
                'type' => 'select',
                'options' => ['LED', 'LCD', 'Smart TV']
            ],
            'Screen Size (Inch)' => [
                'type' => 'select',
                'options' => ['24"', '32"', '43"', '55"+']
            ],
            'Mount Type' => [
                'type' => 'select',
                'options' => ['Wall Mounted', 'Table Stand']
            ],
            'Door Type' => [
                'type' => 'select',
                'options' => ['Single', 'Double', 'Side-by-Side']
            ],
            'Capacity (Liters - Fridge)' => [
                'type' => 'select',
                'options' => ['190L', '250L', '350L', '500L+']
            ],
        ];

        $attributeModels = [];
        foreach ($attributesConfig as $name => $config) {
            $attribute = Attribute::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => ['en' => $name, 'hi' => $name],
                    'type' => $config['type'],
                    'status' => true
                ]
            );
            $attributeModels[$name] = $attribute;

            foreach ($config['options'] as $index => $optValue) {
                AttributeOption::updateOrCreate(
                    ['attribute_id' => $attribute->id, 'value->en' => $optValue],
                    [
                        'value' => ['en' => $optValue, 'hi' => $optValue],
                        'sort_order' => $index
                    ]
                );
            }
        }

        // Get common attributes
        $conditionAttr = Attribute::where('slug', 'condition')->first();

        // 2. Find Home Appliances Sub-category
        $homeAppliances = Category::where('name->en', 'Home Appliances')->first();
        if (!$homeAppliances) {
            $eWasteType = \App\Models\CategoryType::where('slug', 'e-waste')->first();
            $homeAppliances = Category::updateOrCreate(
                ['slug' => 'e-waste-home-appliances'],
                [
                    'name' => ['en' => 'Home Appliances', 'hi' => 'घरेलू उपकरण'],
                    'category_type_id' => $eWasteType ? $eWasteType->id : null,
                    'status' => true
                ]
            );
        }

        // 3. Create/Update Items
        $items = [
            'Air Conditioner' => [
                'hi' => 'एयर कंडीशनर',
                'price' => 3030,
                'attributes' => ['Brand', 'Capacity (Tons)', 'Body Type']
            ],
            'Microwave' => [
                'hi' => 'माइक्रोवेव',
                'price' => 1170,
                'attributes' => ['Brand', 'Microwave Type', 'Capacity (Liters - Microwave)']
            ],
            'Washing Machine' => [
                'hi' => 'वाशिंग मशीन',
                'price' => 3030,
                'attributes' => ['Brand', 'Machine Type', 'Capacity (kg)', 'Body Type']
            ],
            'Television' => [
                'hi' => 'टेलीविज़न',
                'price' => 1940,
                'attributes' => ['Brand', 'Display Type', 'Screen Size (Inch)', 'Mount Type']
            ],
            'Refrigerator' => [
                'hi' => 'रेफ्रिजरेटर',
                'price' => 2780,
                'attributes' => ['Brand', 'Door Type', 'Capacity (Liters - Fridge)']
            ]
        ];

        foreach ($items as $itemName => $itemData) {
            $item = Category::updateOrCreate(
                ['slug' => Str::slug($itemName)],
                [
                    'name' => ['en' => $itemName, 'hi' => $itemData['hi']],
                    'parent_id' => $homeAppliances->id,
                    'category_type_id' => $homeAppliances->category_type_id,
                    'status' => true,
                    'image_path' => 'categories/' . Str::slug($itemName) . '.png'
                ]
            );

            // Clear existing category attributes first
            CategoryAttribute::where('category_id', $item->id)->delete();

            // Assign Attributes
            foreach ($itemData['attributes'] as $attrName) {
                if (isset($attributeModels[$attrName])) {
                    CategoryAttribute::updateOrCreate([
                        'category_id' => $item->id,
                        'attribute_id' => $attributeModels[$attrName]->id
                    ], [
                        'is_required' => true
                    ]);
                }
            }

            // Assign Condition attribute if exists
            if ($conditionAttr) {
                CategoryAttribute::updateOrCreate([
                    'category_id' => $item->id,
                    'attribute_id' => $conditionAttr->id
                ], [
                    'is_required' => true
                ]);
            }

            // 4. Create Pricing Rule
            PricingRule::updateOrCreate(
                ['category_id' => $item->id],
                [
                    'base_price' => $itemData['price'],
                    'currency' => 'INR',
                    'status' => true
                ]
            );
        }
    }
}
