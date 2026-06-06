<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\CategoryType;
use App\Models\PricingRule;
use App\Models\PricingVariantRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ScrapSellingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $hasAdjustmentType = Schema::hasColumn('pricing_rules', 'adjustment_type');
        $hasAdjustmentValue = Schema::hasColumn('pricing_rules', 'adjustment_value');
        $hasRequiresDetails = Schema::hasColumn('categories', 'requires_details');
        $hasVariantRulesTable = Schema::hasTable('pricing_variant_rules');

        // Global estimate attributes for dynamic groups.
        $attributesConfig = [
            'Material Type' => ['Metal', 'Plastic', 'Mixed'],
            'Pickup Size' => ['Small', 'Medium', 'Large'],
            'Condition' => ['Working', 'Refurbished', 'Scrap', 'Non-Working'],
        ];

        $attributeModels = [];
        foreach ($attributesConfig as $attrName => $options) {
            $attribute = Attribute::updateOrCreate(
                ['slug' => Str::slug($attrName)],
                [
                    'name' => ['en' => $attrName, 'hi' => $attrName],
                    'type' => 'select',
                    'status' => true,
                ]
            );
            $attributeModels[$attrName] = $attribute;

            foreach ($options as $index => $value) {
                AttributeOption::updateOrCreate(
                    ['attribute_id' => $attribute->id, 'value->en' => $value],
                    [
                        'value' => ['en' => $value, 'hi' => $value],
                        'sort_order' => $index,
                    ]
                );
            }
        }

        // Updated structure from latest business input + Scrap Description List.xlsx.
        // First three legacy groups are clubbed into one consolidated e-waste type.
        $catalog = [
            'E-Waste, Electrical & Digital Devices' => [
                'image' => 'images/new/categories/cat_e_waste.jpg',
                'subcategories' => [
                    ['name' => 'Air Conditioner', 'base_price' => 3050, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/air_conditioner.jpg'],
                    ['name' => 'Washing Machine', 'base_price' => 850, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/washing_machine.jpg'],
                    ['name' => 'Television', 'base_price' => 200, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/television.jpg'],
                    ['name' => 'Microwave', 'base_price' => 300, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/microwave.jpg'],
                    ['name' => 'Refrigerator', 'base_price' => 900, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/refrigerator.jpg'],
                    ['name' => 'Mixer Grinder', 'base_price' => 130, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/mixer_grinder.jpg'],
                    ['name' => 'Kitchen Chimney', 'base_price' => 200, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/kitchen_chimney.jpg'],
                    ['name' => 'Water Purifier', 'base_price' => 150, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/water_purifier.jpg'],
                    ['name' => 'Mobile Phone', 'base_price' => 150, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/mobile_phone.jpg'],
                    ['name' => 'Laptop', 'base_price' => 700, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/laptop.jpg'],
                    ['name' => 'Cables & Wires', 'base_price' => 40, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/cables_wires.jpg'],
                    ['name' => 'CPU Cabinet', 'base_price' => 450, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/cpu_cabinet.jpg'],
                    ['name' => 'Desktop Computer', 'base_price' => 800, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/desktop_computer.jpg'],
                    ['name' => 'CRT Monitor', 'base_price' => 200, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/crt_monitor.jpg'],
                    ['name' => 'LCD Monitor', 'base_price' => 100, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/lcd_monitor.jpg'],
                    ['name' => 'LED Monitor', 'base_price' => 100, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/led_monitor.jpg'],
                    ['name' => 'Mouse', 'base_price' => 5, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/mouse.jpg'],
                    ['name' => 'Keyboard', 'base_price' => 18, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/keyboard.jpg'],
                    ['name' => 'Motherboard', 'base_price' => 150, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/motherboard.jpg'],
                    ['name' => 'Hard Disk Drive', 'base_price' => 120, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/hard_disk_drive.jpg'],
                    ['name' => 'Server', 'base_price' => 1800, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/server.jpg'],
                    ['name' => 'RAM', 'base_price' => 80, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/ram.jpg'],
                    ['name' => 'Printer', 'base_price' => 200, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/printer.jpg'],
                    ['name' => 'Scanner', 'base_price' => 100, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/scanner.jpg'],
                    ['name' => 'Tablet', 'base_price' => 150, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/tablet.jpg'],
                    ['name' => 'Charger', 'base_price' => 5, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/charger.jpg'],
                    ['name' => 'Laptop Adapter', 'base_price' => 15, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/adapter.jpg'],
                    ['name' => 'Mobile Adaptor', 'base_price' => 3, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/mobile_adaptor.jpg'],
                    ['name' => 'Power Bank', 'base_price' => 10, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/power_bank.jpg'],
                    ['name' => 'Earbuds/Earphone', 'base_price' => 3, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/earphones_earbuds.jpg'],
                    ['name' => 'Headphones', 'base_price' => 10, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/headphones.jpg'],
                    ['name' => 'Induction Cooktop', 'base_price' => 60, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/induction_cooktop.jpg'],
                    ['name' => 'UPS 600 VA With Battery', 'base_price' => 200, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/ups_with_battery.jpg'],
                    ['name' => 'UPS 600 VA Without Battery', 'base_price' => 100, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/ups_without_battery.jpg'],
                    ['name' => 'Inverter With Battery', 'base_price' => 1500, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/inverter_with_battery.jpg'],
                    ['name' => 'Inverter Without Battery', 'base_price' => 300, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/inverter_without_battery.jpg'],
                    ['name' => 'Geyser', 'base_price' => 150, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/geyser.jpg'],
                    ['name' => 'Ceiling Fan / Wall Mounted Fan', 'base_price' => 150, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/ceiling_wall_fan.jpg'],
                    ['name' => 'Table Fan / Stand Fan', 'base_price' => 150, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/table_stand_fan.jpg'],
                    ['name' => 'Air Cooler', 'base_price' => 200, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/air_cooler.jpg'],
                ],
            ],
            'Metals, Power & Energy Hub' => [
                'image' => 'images/new/categories/cat_metals.jpg',
                'subcategories' => [
                    ['name' => 'MS Scrap', 'base_price' => 25, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/ms_scrap.jpg'],
                    ['name' => 'Cast Iron Scrap', 'base_price' => 25, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/cast_iron_scrap.jpg'],
                    ['name' => 'Heavy Melting Scrap', 'base_price' => 27, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/heavy_melting_scrap.jpg'],
                    ['name' => 'Iron Rod / Saria Scrap', 'base_price' => 27, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/iron_rod_saria.jpg'],
                    ['name' => 'Old Steel Pipes & Plates', 'base_price' => 45, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/old_steel_pipes_plates.jpg'],
                    ['name' => 'Machinery Iron Parts', 'base_price' => 27, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/machinery_iron_parts.jpg'],
                    ['name' => 'Copper Wire', 'base_price' => 350, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/copper_wire.jpg'],
                    ['name' => 'Copper', 'base_price' => 600, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/copper.jpg'],
                    ['name' => 'Bras', 'base_price' => 280, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/bras.jpg'],
                    ['name' => 'Aluminium Scrap', 'base_price' => 240, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/aluminium_scrap.jpg'],
                    ['name' => 'Lead Scrap', 'base_price' => 80, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/lead_scrap.jpg'],
                    ['name' => 'Zinc Scrap', 'base_price' => 40, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/zinc_scrap.jpg'],
                    ['name' => 'Nickel Scrap', 'base_price' => 120, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/nickel_scrap.jpg'],
                    ['name' => 'CNC Cutting Scrap', 'base_price' => 27, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/cnc_cutting_scrap.jpg'],
                    ['name' => 'Punching Scrap', 'base_price' => 27, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/punching_scrap.jpg'],
                    ['name' => 'Metal Turning (Boring Scrap)', 'base_price' => 27, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/metal_turning_boring.jpg'],
                    ['name' => 'Fabrication Waste', 'base_price' => 27, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/fabrication_waste.jpg'],
                    ['name' => 'Iron Nails', 'base_price' => 27, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/iron_nails.jpg'],
                    ['name' => 'Battery', 'base_price' => 80, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/battery.jpg'],
                ],
            ],
            'Plastic Scrap Categories' => [
                'image' => 'images/new/categories/cat_plastic.jpg',
                'subcategories' => [
                    ['name' => 'Water Bottles', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/water_bottles.jpg'],
                    ['name' => 'Soft Drink Bottles', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/soft_drink_bottles.jpg'],
                    ['name' => 'Transparent Oil Bottles', 'base_price' => 12, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/transparent_oil_bottles.jpg'],
                    ['name' => 'Detergent Bottles', 'base_price' => 15, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/detergent_bottles.jpg'],
                    ['name' => 'Chemical Cans', 'base_price' => 15, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/chemical_cans.jpg'],
                    ['name' => 'Plastic Drums', 'base_price' => 17, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/plastic_drums.jpg'],
                    ['name' => 'Pipes', 'base_price' => 12, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/pipes.jpg'],
                    ['name' => 'Wire Insulations', 'base_price' => 11, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/wire_insulations.jpg'],
                    ['name' => 'Flex Sheets', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/flex_sheets.jpg'],
                    ['name' => 'Carry Bags', 'base_price' => 9, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/carry_bags.jpg'],
                    ['name' => 'Packaging Films', 'base_price' => 9, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/packaging_films.jpg'],
                    ['name' => 'Stretch Wrap', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/stretch_wrap.jpg'],
                    ['name' => 'Plastic Crates', 'base_price' => 18, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/plastic_crates.jpg'],
                    ['name' => 'Plastic Chairs', 'base_price' => 16, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/plastic_chairs.jpg'],
                    ['name' => 'Battery Boxes', 'base_price' => 17, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/battery_boxes.jpg'],
                    ['name' => 'Thermocol', 'base_price' => 8, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/thermocol.jpg'],
                    ['name' => 'Disposable Cups', 'base_price' => 8, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/disposable_cups.jpg'],
                    ['name' => 'Foam Packaging', 'base_price' => 8, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/foam_packaging.jpg'],
                ],
            ],
            'Paper, Plastic & Glass Recyclables' => [
                'image' => 'images/new/categories/cat_paper_glass.jpg',
                'subcategories' => [
                    ['name' => 'Newspaper', 'base_price' => 12, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/newspaper.jpg'],
                    ['name' => 'Cardboard', 'base_price' => 12, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/cardboard.jpg'],
                    ['name' => 'Plastic Bottles', 'base_price' => 14, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/water_bottles.jpg'],
                    ['name' => 'Glass Bottles', 'base_price' => 6, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/glass_bottles.jpg'],
                    ['name' => 'White Record Paper', 'base_price' => 14, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/white_record_paper.jpg'],
                    ['name' => 'Office Paper Scrap', 'base_price' => 14, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/office_paper_scrap.jpg'],
                    ['name' => 'Mixed Paper', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/mixed_paper.jpg'],
                    ['name' => 'Books Scrap', 'base_price' => 13, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/books_scrap.jpg'],
                    ['name' => 'Notebook Scrap', 'base_price' => 11, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/notebook_scrap.jpg'],
                    ['name' => 'Brown Corrugated Carton Scrap', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/brown_corrugated_carton.jpg'],
                    ['name' => 'Duplex Board Carton Scrap', 'base_price' => 11, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/duplex_board_carton.jpg'],
                    ['name' => 'Corrugated Sheet / Punching Waste', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/corrugated_sheet_punching.jpg'],
                ],
            ],
            'Old Furniture' => [
                'image' => 'images/new/categories/cat_furniture.jpg',
                'subcategories' => [
                    ['name' => 'Wooden Chair', 'base_price' => 150, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/wooden_chair.jpg'],
                    ['name' => 'Steel Cupboard', 'base_price' => 500, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/steel_cupboard.jpg'],
                    ['name' => 'Study Table', 'base_price' => 150, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/study_table.jpg'],
                    ['name' => 'Sofa Set', 'base_price' => 300, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/sofa_set.jpg'],
                    ['name' => 'Bed', 'base_price' => 1600, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/bed.jpg'],
                    ['name' => 'Dressing Table', 'base_price' => 600, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/dressing_table.jpg'],
                    ['name' => 'Dining Table', 'base_price' => 750, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/dining_table.jpg'],
                    ['name' => 'Work Stations', 'base_price' => 900, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/work_stations.jpg'],
                    ['name' => 'Reception Table', 'base_price' => 1500, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/reception_table.jpg'],
                    ['name' => 'Boss Chair', 'base_price' => 900, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/boss_chair.jpg'],
                    ['name' => 'Settee Sofa', 'base_price' => 900, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/settee_sofa.jpg'],
                ],
            ],
            'Hazardous Waste' => [
                'image' => 'images/new/categories/cat_hazardous.jpg',
                'subcategories' => [
                    ['name' => 'Lithium-Ion Battery', 'base_price' => 120, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/lithium_ion_battery.jpg'],
                    ['name' => 'Inverter Battery', 'base_price' => 65, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/inverter_battery.jpg'],
                    ['name' => 'Used Oil', 'base_price' => 20, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/used_oil.jpg'],
                    ['name' => 'Lead', 'base_price' => 105, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/lead_scrap.jpg'],
                    ['name' => 'CFL Bulb', 'base_price' => 1, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/cfl_bulb.jpg'],
                    ['name' => 'Tube Light', 'base_price' => 2, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/tube_light.jpg'],
                    ['name' => 'Bulb', 'base_price' => 1, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/bulb.jpg'],
                ],
            ],
            'Vehicle & Machinery Waste' => [
                'image' => 'images/new/categories/cat_vehicles.jpg',
                'subcategories' => [
                    ['name' => 'Scooty', 'base_price' => 3800, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/scooty.jpg'],
                    ['name' => 'Bike', 'base_price' => 5500, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/bike.jpg'],
                    ['name' => 'Car', 'base_price' => 28000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/car.jpg'],
                    ['name' => 'Tata Ace', 'base_price' => 28000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/tata_ace.jpg'],
                    ['name' => 'Pick Bolero', 'base_price' => 32000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/pick_bolero.jpg'],
                    ['name' => 'Tata 407', 'base_price' => 50000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/tata_407.jpg'],
                    ['name' => 'Bus', 'base_price' => 80000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/bus.jpg'],
                    ['name' => 'Truck', 'base_price' => 110000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/truck.jpg'],
                ],
            ],
        ];

        // Standard base price matrix from client pricing workbook.
        $standardBasePrices = [
            'Air Conditioner' => 3050,
            'Washing Machine' => 850,
            'Television' => 200,
            'Microwave' => 300,
            'Refrigerator' => 900,
            'Mixer Grinder' => 130,
            'Kitchen Chimney' => 200,
            'Water Purifier' => 150,
            'Mobile Phone' => 150,
            'Laptop' => 700,
            'Cables & Wires' => 40,
            'CPU Cabinet' => 450,
            'Desktop Computer' => 800,
            'CRT Monitor' => 200,
            'LCD Monitor' => 100,
            'LED Monitor' => 100,
            'Mouse' => 5,
            'Keyboard' => 18,
            'Motherboard' => 150,
            'Hard Disk Drive' => 120,
            'Server' => 1800,
            'RAM' => 80,
            'Printer' => 200,
            'Scanner' => 100,
            'Tablet' => 150,
            'Charger' => 5,
            'Laptop Adapter' => 15,
            'Mobile Adaptor' => 3,
            'Power Bank' => 10,
            'Earbuds/Earphone' => 3,
            'Headphones' => 10,
            'MS Scrap' => 25,
            'Cast Iron Scrap' => 25,
            'Heavy Melting Scrap' => 27,
            'Iron Rod / Saria Scrap' => 27,
            'Old Steel Pipes & Plates' => 45,
            'Machinery Iron Parts' => 27,
            'Copper Wire' => 350,
            'Copper' => 600,
            'Bras' => 280,
            'Aluminium Scrap' => 240,
            'Lead Scrap' => 80,
            'Zinc Scrap' => 40,
            'Nickel Scrap' => 120,
            'CNC Cutting Scrap' => 27,
            'Punching Scrap' => 27,
            'Metal Turning (Boring Scrap)' => 27,
            'Fabrication Waste' => 27,
            'Iron Nails' => 27,
            'Battery' => 80,
            'Water Bottles' => 10,
            'Soft Drink Bottles' => 10,
            'Transparent Oil Bottles' => 12,
            'Detergent Bottles' => 15,
            'Chemical Cans' => 15,
            'Plastic Drums' => 17,
            'Pipes' => 12,
            'Wire Insulations' => 11,
            'Flex Sheets' => 10,
            'Carry Bags' => 9,
            'Packaging Films' => 9,
            'Stretch Wrap' => 10,
            'Plastic Crates' => 18,
            'Plastic Chairs' => 16,
            'Battery Boxes' => 17,
            'Thermocol' => 8,
            'Disposable Cups' => 8,
            'Foam Packaging' => 8,
            'Newspaper' => 12,
            'Cardboard' => 12,
            'Plastic Bottles' => 14,
            'Glass Bottles' => 6,
            'White Record Paper' => 14,
            'Office Paper Scrap' => 14,
            'Mixed Paper' => 10,
            'Books Scrap' => 13,
            'Notebook Scrap' => 11,
            'Brown Corrugated Carton Scrap' => 10,
            'Duplex Board Carton Scrap' => 11,
            'Corrugated Sheet / Punching Waste' => 10,
            'Wooden Chair' => 150,
            'Steel Cupboard' => 500,
            'Study Table' => 150,
            'Sofa Set' => 300,
            'Bed' => 1600,
            'Dressing Table' => 600,
            'Dining Table' => 750,
            'Work Stations' => 900,
            'Reception Table' => 1500,
            'Boss Chair' => 900,
            'Settee Sofa' => 900,
            'Lithium-Ion Battery' => 120,
            'Inverter Battery' => 65,
            'Used Oil' => 20,
            'Lead' => 105,
            'CFL Bulb' => 1,
            'Tube Light' => 2,
            'Bulb' => 1,
            'Scooty' => 3800,
            'Bike' => 5500,
            'Car' => 28000,
            'Tata Ace' => 28000,
            'Pick Bolero' => 32000,
            'Tata 407' => 50000,
            'Bus' => 80000,
            'Truck' => 110000,
            'Induction Cooktop' => 60,
            'UPS 600 VA With Battery' => 200,
            'UPS 600 VA Without Battery' => 100,
            'Inverter With Battery' => 1500,
            'Inverter Without Battery' => 300,
            'Geyser' => 150,
            'Ceiling Fan / Wall Mounted Fan' => 150,
            'Table Fan / Stand Fan' => 150,
            'Air Cooler' => 200,
        ];

        $dynamicEstimateParents = [
            'E-Waste, Electrical & Digital Devices',
            'Old Furniture',
            'Hazardous Waste',
            'Vehicle & Machinery Waste',
            'Metals, Power & Energy Hub',
            'Plastic Scrap Categories',
            'Paper, Plastic & Glass Recyclables',
        ];
        $corporateEnabledTypes = [
            'E-Waste, Electrical & Digital Devices',
            'Metals, Power & Energy Hub',
            'Old Furniture',
        ];
        $specializedCategoryNames = [
            'Air Conditioner',
            'Washing Machine',
            'Television',
            'Microwave',
            'Refrigerator',
            'Mixer Grinder',
            'Kitchen Chimney',
            'Water Purifier',
            'Mobile Phone',
            'Laptop',
            'Cables & Wires',
            'CPU Cabinet',
            'Wooden Chair',
            'Steel Cupboard',
            'Study Table',
            'Sofa Set',
        ];
        $seededSubCategoriesByName = [];

        // Keep old umbrella + old split e-waste type names inactive after consolidation.
        CategoryType::where('slug', 'scrap-selling')->update(['status' => false]);
        CategoryType::whereIn('slug', [
            Str::slug('Smart Electrical Appliances'),
            Str::slug('Premium Kitchen Appliances'),
            Str::slug('Digital Gadgets & IT Gear'),
        ])->update(['status' => false]);

        foreach ($catalog as $typeName => $typeData) {
            $type = CategoryType::updateOrCreate(
                ['slug' => Str::slug($typeName)],
                [
                    'name' => ['en' => $typeName, 'hi' => $typeName],
                    'status' => true,
                    'show_in_corporate_booking' => in_array($typeName, $corporateEnabledTypes, true),
                    'image_path' => $typeData['image'],
                ]
            );

            $requestedSubcategorySlugs = collect($typeData['subcategories'])
                ->map(fn($sub) => Str::slug($typeName . '-' . $sub['name']))
                ->all();

            Category::where('category_type_id', $type->id)
                ->whereNull('parent_id')
                ->whereNotIn('slug', $requestedSubcategorySlugs)
                ->update(['status' => false]);

            foreach ($typeData['subcategories'] as $sub) {
                $resolvedBasePrice = $standardBasePrices[$sub['name']] ?? $sub['base_price'];
                $subCategory = Category::updateOrCreate(
                    [
                        'slug' => Str::slug($typeName . '-' . $sub['name']),
                        'category_type_id' => $type->id,
                        'parent_id' => null,
                    ],
                    tap([
                        'name' => ['en' => $sub['name'], 'hi' => $sub['name']],
                        'status' => true,
                        'image_path' => $sub['image'],
                    ], function (&$payload) use ($hasRequiresDetails, $specializedCategoryNames, $sub) {
                        if ($hasRequiresDetails) {
                            $payload['requires_details'] = in_array($sub['name'], $specializedCategoryNames, true);
                        }
                    })
                );

                PricingRule::updateOrCreate(
                    ['category_id' => $subCategory->id, 'attribute_option_id' => null],
                    [
                        'pricing_type' => $sub['pricing_type'],
                        'base_price' => $resolvedBasePrice,
                        'min_quantity' => 1,
                        'status' => true,
                    ]
                );

                if (in_array($typeName, $dynamicEstimateParents, true)) {
                    $seededSubCategoriesByName[$sub['name']] = $subCategory;
                }
            }
        }

        $this->seedSpecializedCategoryProfiles(
            $seededSubCategoriesByName,
            $hasAdjustmentType,
            $hasAdjustmentValue
        );

        $this->seedVariantPricingRules($seededSubCategoriesByName, $hasVariantRulesTable);
    }

    private function seedPercentageRules(
        int $categoryId,
        array $attributeModels,
        bool $hasAdjustmentType,
        bool $hasAdjustmentValue,
        string $pricingType
    ): void {
        $percentageMap = [
            'Material Type' => [
                'Metal' => 2,
                'Plastic' => 1,
                'Mixed' => 0,
            ],
            'Pickup Size' => [
                'Small' => -1,
                'Medium' => 0,
                'Large' => 2,
            ],
            'Condition' => [
                'Working' => 2,
                'Refurbished' => 1,
                'Scrap' => -1,
                'Non-Working' => -2,
            ],
        ];

        foreach ($percentageMap as $attributeName => $optionAdjustments) {
            $attribute = $attributeModels[$attributeName] ?? null;
            if (!$attribute) {
                continue;
            }

            foreach ($optionAdjustments as $optionText => $percent) {
                $option = AttributeOption::where('attribute_id', $attribute->id)
                    ->where('value->en', $optionText)
                    ->first();

                if (!$option) {
                    continue;
                }

                $payload = [
                    'pricing_type' => $pricingType,
                    'base_price' => 0,
                    'min_quantity' => 1,
                    'status' => true,
                ];

                if ($hasAdjustmentType) {
                    $payload['adjustment_type'] = 'percentage';
                }
                if ($hasAdjustmentValue) {
                    $payload['adjustment_value'] = $percent;
                }

                PricingRule::updateOrCreate(
                    [
                        'category_id' => $categoryId,
                        'attribute_option_id' => $option->id,
                    ],
                    $payload
                );
            }
        }
    }

    private function seedSpecializedCategoryProfiles(
        array $seededSubCategoriesByName,
        bool $hasAdjustmentType,
        bool $hasAdjustmentValue
    ): void {
        $profiles = [
            'Air Conditioner' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['Daikin', 'Voltas', 'LG', 'Samsung', 'Blue Star', 'Hitachi', 'Other'],
                    'Cooling Capacity (Ton)' => ['0.8-1 Ton', '1.5 Ton', '2 Ton', '3 Ton', '5.5 Ton', '8 Ton'],
                    'Working Condition' => ['Working', 'Non-Working', 'Scrap'],
                    'Usage Age (If Working)' => ['0-3 Years', '3-6 Years', '6+ Years'],
                ],
                'adjustments' => [
                    'Brand' => ['Daikin' => 5, 'Voltas' => 2, 'LG' => 0, 'Samsung' => 2, 'Blue Star' => 4, 'Hitachi' => 3, 'Other' => 0],
                    'Cooling Capacity (Ton)' => ['0.8-1 Ton' => 0, '1.5 Ton' => 45, '2 Ton' => 90, '3 Ton' => 135, '5.5 Ton' => 200, '8 Ton' => 300],
                    'Working Condition' => ['Working' => 5, 'Non-Working' => 0, 'Scrap' => -3],
                    'Usage Age (If Working)' => ['0-3 Years' => 3, '3-6 Years' => 2, '6+ Years' => 0],
                ],
            ],
            'Washing Machine' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['LG', 'Samsung', 'Whirlpool', 'Bosch', 'IFB', 'Godrej', 'Other'],
                    'Drum Capacity (Kg)' => ['Up to 6 Kg', '6.5-8 Kg', '8.5+ Kg'],
                    'Machine Type' => ['Front Load', 'Top Load', 'Semi Automatic'],
                    'Working Condition' => ['Working', 'Non-Working', 'Scrap'],
                    'Usage Age (If Working)' => ['0-3 Years', '3-6 Years', '6+ Years'],
                ],
                'adjustments' => [
                    'Brand' => ['LG' => 5, 'Samsung' => 5, 'Whirlpool' => 8, 'Bosch' => 3, 'IFB' => 2, 'Godrej' => 2, 'Other' => 0],
                    'Drum Capacity (Kg)' => ['Up to 6 Kg' => 0, '6.5-8 Kg' => 2, '8.5+ Kg' => 3],
                    'Machine Type' => ['Front Load' => 30, 'Top Load' => 2, 'Semi Automatic' => 0],
                    'Working Condition' => ['Working' => 7, 'Non-Working' => 0, 'Scrap' => -5],
                    'Usage Age (If Working)' => ['0-3 Years' => 10, '3-6 Years' => 0, '6+ Years' => -3],
                ],
            ],
            'Television' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['Samsung', 'LG', 'Sony', 'Mi', 'TCL', 'Panasonic', 'Other'],
                    'Screen Size (Inch)' => ['Up to 32"', '33-43"', '44-55"', '56"+'],
                    'Display Type' => ['LED', 'LCD', 'Plasma', 'CRT'],
                    'Working Condition' => ['Working', 'Non-Working', 'Scrap'],
                    'Usage Age (If Working)' => ['0-3 Years', '3-6 Years', '6+ Years'],
                ],
                'adjustments' => [
                    'Brand' => ['Samsung' => 3, 'LG' => 2, 'Sony' => 4, 'Mi' => 0, 'TCL' => 1, 'Panasonic' => 1, 'Other' => 0],
                    'Screen Size (Inch)' => ['Up to 32"' => 0, '33-43"' => 10, '44-55"' => 25, '56"+' => 50],
                    'Display Type' => ['LED' => 5, 'LCD' => -4, 'Plasma' => -6, 'CRT' => -6],
                    'Working Condition' => ['Working' => 4, 'Non-Working' => -4, 'Scrap' => -8],
                    'Usage Age (If Working)' => ['0-3 Years' => 3, '3-6 Years' => 0, '6+ Years' => -3],
                ],
            ],
            'Microwave' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['LG', 'Samsung', 'IFB', 'Panasonic', 'Whirlpool', 'Other'],
                    'Type' => ['Solo', 'Grill', 'Convection'],
                    'Working Condition' => ['Working', 'Non-Working', 'Scrap'],
                    'Usage Age (If Working)' => ['0-3 Years', '3-6 Years', '6+ Years'],
                ],
                'adjustments' => [
                    'Brand' => ['LG' => 2, 'Samsung' => 3, 'IFB' => 0, 'Panasonic' => 0, 'Whirlpool' => 0, 'Other' => 0],
                    'Type' => ['Solo' => 0, 'Grill' => 2, 'Convection' => 4],
                    'Working Condition' => ['Working' => 3, 'Non-Working' => 0, 'Scrap' => -4],
                    'Usage Age (If Working)' => ['0-3 Years' => 3, '3-6 Years' => 2, '6+ Years' => 0],
                ],
            ],
            'Refrigerator' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['LG', 'Samsung', 'Whirlpool', 'Godrej', 'Haier', 'Panasonic', 'Other'],
                    'Capacity' => ['Up to 200 L', '201-300 L', '301-450 L', '450+ L'],
                    'Door Type' => ['Single Door', 'Double Door', 'Side-by-Side'],
                    'Working Condition' => ['Working', 'Non-Working', 'Scrap'],
                    'Usage Age (If Working)' => ['0-3 Years', '3-6 Years', '6+ Years'],
                ],
                'adjustments' => [
                    'Brand' => ['LG' => 5, 'Samsung' => 6, 'Whirlpool' => 4, 'Godrej' => 3, 'Haier' => 1, 'Panasonic' => 2, 'Other' => 0],
                    'Capacity' => ['Up to 200 L' => -2, '201-300 L' => 1, '301-450 L' => 4, '450+ L' => 6],
                    'Door Type' => ['Single Door' => 0, 'Double Door' => 2, 'Side-by-Side' => 5],
                    'Working Condition' => ['Working' => 4, 'Non-Working' => -4, 'Scrap' => -8],
                    'Usage Age (If Working)' => ['0-3 Years' => 3, '3-6 Years' => 0, '6+ Years' => -3],
                ],
            ],
            'Mixer Grinder' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['Philips', 'Preethi', 'Bajaj', 'Sujata', 'Havells', 'Other'],
                    'Jar Count' => ['1 Jar', '2 Jars', '3+ Jars'],
                    'Working Condition' => ['Working', 'Non-Working', 'Scrap'],
                ],
                'adjustments' => [
                    'Brand' => ['Philips' => 2, 'Preethi' => 2, 'Bajaj' => 1, 'Sujata' => 2, 'Havells' => 1, 'Other' => 0],
                    'Jar Count' => ['1 Jar' => -2, '2 Jars' => 0, '3+ Jars' => 2],
                    'Working Condition' => ['Working' => 4, 'Non-Working' => -4, 'Scrap' => -8],
                ],
            ],
            'Kitchen Chimney' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['Elica', 'Faber', 'Hindware', 'Glen', 'Kaff', 'Other'],
                    'Suction Capacity' => ['Below 1000 m3/h', '1000-1200 m3/h', '1200+ m3/h'],
                    'Working Condition' => ['Working', 'Non-Working', 'Scrap'],
                ],
                'adjustments' => [
                    'Brand' => ['Elica' => 2, 'Faber' => 2, 'Hindware' => 1, 'Glen' => 1, 'Kaff' => 1, 'Other' => 0],
                    'Suction Capacity' => ['Below 1000 m3/h' => -2, '1000-1200 m3/h' => 1, '1200+ m3/h' => 3],
                    'Working Condition' => ['Working' => 4, 'Non-Working' => -4, 'Scrap' => -8],
                ],
            ],
            'Water Purifier' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['Kent', 'Aquaguard', 'Pureit', 'Livpure', 'AO Smith', 'Other'],
                    'Purifier Type' => ['RO', 'RO+UV', 'RO+UV+UF'],
                    'Working Condition' => ['Working', 'Non-Working', 'Scrap'],
                ],
                'adjustments' => [
                    'Brand' => ['Kent' => 2, 'Aquaguard' => 2, 'Pureit' => 1, 'Livpure' => 1, 'AO Smith' => 1, 'Other' => 0],
                    'Purifier Type' => ['RO' => 0, 'RO+UV' => 2, 'RO+UV+UF' => 4],
                    'Working Condition' => ['Working' => 4, 'Non-Working' => -4, 'Scrap' => -8],
                ],
            ],
            'Mobile Phone' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['Apple', 'Samsung', 'OnePlus', 'Motorola', 'Nothing', 'Xiaomi', 'Vivo', 'Oppo', 'Realme', 'Other'],
                    'Storage Variant' => ['64 GB', '128 GB', '256 GB', '512 GB'],
                    'Device Age' => ['0-1 Year', '1-2 Years', '2-3 Years', '3+ Years'],
                    'Screen Condition' => ['No Damage', 'Minor Scratch', 'Cracked'],
                    'Functional Status' => ['Fully Working', 'Partially Working', 'Not Working'],
                ],
                'adjustments' => [
                    'Brand' => ['Apple' => 8, 'Samsung' => 5, 'OnePlus' => 4, 'Motorola' => 2, 'Nothing' => 3, 'Xiaomi' => 2, 'Vivo' => 1, 'Oppo' => 1, 'Realme' => 1, 'Other' => 0],
                    'Storage Variant' => ['64 GB' => -3, '128 GB' => 0, '256 GB' => 4, '512 GB' => 7],
                    'Device Age' => ['0-1 Year' => 6, '1-2 Years' => 2, '2-3 Years' => -2, '3+ Years' => -6],
                    'Screen Condition' => ['No Damage' => 3, 'Minor Scratch' => -2, 'Cracked' => -8],
                    'Functional Status' => ['Fully Working' => 5, 'Partially Working' => -4, 'Not Working' => -10],
                ],
            ],
            'Laptop' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Brand' => ['Apple', 'Dell', 'HP', 'Lenovo', 'Asus', 'Acer', 'MSI', 'Other'],
                    'Processor Tier' => ['Intel i3 / Ryzen 3', 'Intel i5 / Ryzen 5', 'Intel i7+ / Ryzen 7+', 'Apple MacBook'],
                    'Processor Generation' => ['1st-5th Gen', '6th-8th Gen', '9th-10th Gen', '11th-13th Gen', 'Intel', 'M1', 'M2', 'M1 Pro', 'M2 Pro', 'M3 / M3 Pro'],
                    'RAM Variant' => ['4 GB', '8 GB', '16 GB', '32 GB+'],
                    'Storage Type' => ['HDD', 'SSD SATA', 'NVMe SSD'],
                    'Body Condition' => ['Good', 'Minor Dents', 'Major Damage'],
                    'Battery Health' => ['Good', 'Average', 'Poor'],
                    'Functional Status' => ['Fully Working', 'Partially Working', 'Not Working'],
                ],
                'adjustments' => [
                    'Brand' => ['Apple' => 12, 'Dell' => 3, 'HP' => 2, 'Lenovo' => 2, 'Asus' => 2, 'Acer' => 1, 'MSI' => 3, 'Other' => 0],
                    'Processor Tier' => ['Intel i3 / Ryzen 3' => -4, 'Intel i5 / Ryzen 5' => 0, 'Intel i7+ / Ryzen 7+' => 8, 'Apple MacBook' => 0],
                    'Processor Generation' => ['1st-5th Gen' => -12, '6th-8th Gen' => -5, '9th-10th Gen' => 2, '11th-13th Gen' => 8, 'Intel' => 0, 'M1' => 0, 'M2' => 0, 'M1 Pro' => 0, 'M2 Pro' => 0, 'M3 / M3 Pro' => 0],
                    'RAM Variant' => ['4 GB' => -5, '8 GB' => 0, '16 GB' => 5, '32 GB+' => 9],
                    'Storage Type' => ['HDD' => -4, 'SSD SATA' => 2, 'NVMe SSD' => 6],
                    'Body Condition' => ['Good' => 2, 'Minor Dents' => -2, 'Major Damage' => -7],
                    'Battery Health' => ['Good' => 3, 'Average' => 0, 'Poor' => -4],
                    'Functional Status' => ['Fully Working' => 5, 'Partially Working' => -5, 'Not Working' => -12],
                ],
            ],
            'Cables & Wires' => [
                'pricing_type' => 'per_kg',
                'sections' => [
                    'Metal Content' => ['High Copper', 'Mixed', 'Low Copper'],
                    'Insulation State' => ['Clean Stripped', 'Partially Stripped', 'Unstripped'],
                    'Quality Grade' => ['Premium', 'Standard', 'Low'],
                ],
                'adjustments' => [
                    'Metal Content' => ['High Copper' => 6, 'Mixed' => 0, 'Low Copper' => -5],
                    'Insulation State' => ['Clean Stripped' => 4, 'Partially Stripped' => 0, 'Unstripped' => -4],
                    'Quality Grade' => ['Premium' => 3, 'Standard' => 0, 'Low' => -3],
                ],
            ],
            'CPU Cabinet' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Cabinet Type' => ['Branded Desktop', 'Assembled Desktop', 'Bare Cabinet'],
                    'Processor Tier' => ['Intel i3 / Ryzen 3', 'Intel i5 / Ryzen 5', 'Intel i7+ / Ryzen 7+'],
                    'Processor Generation' => ['1st-5th Gen', '6th-8th Gen', '9th-10th Gen', '11th-13th Gen'],
                    'RAM Installed' => ['No RAM', '4-8 GB', '16 GB+'],
                    'Storage Installed' => ['No Storage', 'HDD', 'SSD / NVMe'],
                    'Functional Status' => ['Fully Working', 'Partially Working', 'Not Working'],
                ],
                'adjustments' => [
                    'Cabinet Type' => ['Branded Desktop' => 4, 'Assembled Desktop' => 0, 'Bare Cabinet' => -8],
                    'Processor Tier' => ['Intel i3 / Ryzen 3' => -3, 'Intel i5 / Ryzen 5' => 0, 'Intel i7+ / Ryzen 7+' => 7],
                    'Processor Generation' => ['1st-5th Gen' => -10, '6th-8th Gen' => -4, '9th-10th Gen' => 2, '11th-13th Gen' => 7],
                    'RAM Installed' => ['No RAM' => -5, '4-8 GB' => 0, '16 GB+' => 4],
                    'Storage Installed' => ['No Storage' => -4, 'HDD' => 0, 'SSD / NVMe' => 5],
                    'Functional Status' => ['Fully Working' => 4, 'Partially Working' => -4, 'Not Working' => -10],
                ],
            ],
            'Wooden Chair' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Material' => ['Solid Wood', 'Engineered Wood', 'Plastic', 'Metal', 'Other'],
                    'Size' => ['Small', 'Medium', 'Large'],
                    'Condition' => ['Good', 'Usable', 'Damaged'],
                ],
                'adjustments' => [
                    'Material' => ['Solid Wood' => 6, 'Engineered Wood' => 2, 'Plastic' => -2, 'Metal' => 1, 'Other' => 0],
                    'Size' => ['Small' => -2, 'Medium' => 0, 'Large' => 3],
                    'Condition' => ['Good' => 4, 'Usable' => 0, 'Damaged' => -8],
                ],
            ],
            'Steel Cupboard' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Material' => ['Heavy Steel', 'Light Steel', 'Wood + Steel Mix', 'Other'],
                    'Size' => ['2 Door Compact', '2 Door Standard', '3 Door / Large'],
                    'Condition' => ['Good', 'Usable', 'Damaged'],
                ],
                'adjustments' => [
                    'Material' => ['Heavy Steel' => 7, 'Light Steel' => 2, 'Wood + Steel Mix' => 1, 'Other' => 0],
                    'Size' => ['2 Door Compact' => -2, '2 Door Standard' => 0, '3 Door / Large' => 5],
                    'Condition' => ['Good' => 4, 'Usable' => 0, 'Damaged' => -9],
                ],
            ],
            'Study Table' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Material' => ['Solid Wood', 'Engineered Wood', 'Metal Frame', 'Plastic', 'Other'],
                    'Size' => ['2-3 ft', '4 ft', '5 ft+'],
                    'Condition' => ['Good', 'Usable', 'Damaged'],
                ],
                'adjustments' => [
                    'Material' => ['Solid Wood' => 5, 'Engineered Wood' => 2, 'Metal Frame' => 3, 'Plastic' => -2, 'Other' => 0],
                    'Size' => ['2-3 ft' => -2, '4 ft' => 0, '5 ft+' => 4],
                    'Condition' => ['Good' => 4, 'Usable' => 0, 'Damaged' => -8],
                ],
            ],
            'Sofa Set' => [
                'pricing_type' => 'per_piece',
                'sections' => [
                    'Sofa Type' => ['1 Seater', '2 Seater', '3 Seater', 'L-Shape / 5 Seater+'],
                    'Frame Material' => ['Solid Wood', 'Engineered Wood', 'Metal', 'Other'],
                    'Condition' => ['Good', 'Usable', 'Damaged'],
                ],
                'adjustments' => [
                    'Sofa Type' => ['1 Seater' => -4, '2 Seater' => 0, '3 Seater' => 4, 'L-Shape / 5 Seater+' => 8],
                    'Frame Material' => ['Solid Wood' => 6, 'Engineered Wood' => 2, 'Metal' => 2, 'Other' => 0],
                    'Condition' => ['Good' => 4, 'Usable' => 0, 'Damaged' => -10],
                ],
            ],
        ];

        foreach ($profiles as $categoryName => $profile) {
            /** @var Category|null $category */
            $category = $seededSubCategoriesByName[$categoryName] ?? null;
            if (!$category) {
                continue;
            }

            $attributeIds = [];
            $allowedOptionIds = [];
            foreach ($profile['sections'] as $sectionTitle => $sectionOptions) {
                $attribute = Attribute::updateOrCreate(
                    ['slug' => Str::slug($categoryName . '-' . $sectionTitle)],
                    [
                        'name' => ['en' => $sectionTitle, 'hi' => $sectionTitle],
                        'type' => 'select',
                        'status' => true,
                    ]
                );
                $attributeIds[] = $attribute->id;

                AttributeOption::where('attribute_id', $attribute->id)
                    ->get()
                    ->each(function (AttributeOption $existingOption) use ($sectionOptions) {
                        $value = $existingOption->value['en'] ?? null;
                        if (!in_array($value, $sectionOptions, true)) {
                            $existingOption->delete();
                        }
                    });

                foreach ($sectionOptions as $index => $optionText) {
                    $option = AttributeOption::updateOrCreate(
                        ['attribute_id' => $attribute->id, 'value->en' => $optionText],
                        [
                            'value' => ['en' => $optionText, 'hi' => $optionText],
                            'sort_order' => $index,
                        ]
                    );
                    $allowedOptionIds[] = $option->id;

                    $percent = (float) ($profile['adjustments'][$sectionTitle][$optionText] ?? 0);
                    $payload = [
                        'pricing_type' => $profile['pricing_type'],
                        'base_price' => 0,
                        'min_quantity' => 1,
                        'status' => true,
                    ];
                    if ($hasAdjustmentType) {
                        $payload['adjustment_type'] = 'percentage';
                    }
                    if ($hasAdjustmentValue) {
                        $payload['adjustment_value'] = $percent;
                    }

                    PricingRule::updateOrCreate(
                        [
                            'category_id' => $category->id,
                            'attribute_option_id' => $option->id,
                        ],
                        $payload
                    );
                }
            }

            PricingRule::where('category_id', $category->id)
                ->whereNotNull('attribute_option_id')
                ->whereNotIn('attribute_option_id', $allowedOptionIds)
                ->delete();

            $syncPayload = [];
            foreach ($attributeIds as $attributeId) {
                $syncPayload[$attributeId] = ['is_required' => true];
            }
            $category->attributes()->sync($syncPayload);
        }
    }

    private function seedVariantPricingRules(array $seededSubCategoriesByName, bool $hasVariantRulesTable): void
    {
        if (!$hasVariantRulesTable) {
            return;
        }

        $variantPricingRules = [
            'Air Conditioner' => [
                [
                    'title' => 'Air Conditioner | 0.8-1 Ton',
                    'base_price' => 3050,
                    'option_values' => ['0.8-1 Ton'],
                    'source_column' => 'X',
                ],
                [
                    'title' => 'Air Conditioner | 1.5 Ton',
                    'base_price' => 4422.5,
                    'option_values' => ['1.5 Ton'],
                    'source_column' => 'Y',
                ],
                [
                    'title' => 'Air Conditioner | 2 Ton',
                    'base_price' => 5795,
                    'option_values' => ['2 Ton'],
                    'source_column' => 'Z',
                ],
                [
                    'title' => 'Air Conditioner | 3 Ton',
                    'base_price' => 7167.5,
                    'option_values' => ['3 Ton'],
                    'source_column' => 'AA',
                ],
                [
                    'title' => 'Air Conditioner | 5.5 Ton',
                    'base_price' => 9150,
                    'option_values' => ['5.5 Ton'],
                    'source_column' => 'AB',
                ],
                [
                    'title' => 'Air Conditioner | 8 Ton',
                    'base_price' => 12200,
                    'option_values' => ['8 Ton'],
                    'source_column' => 'AC',
                ],
            ],
            'Washing Machine' => [
                [
                    'title' => 'Washing Machine | Front Load | Up to 6 Kg',
                    'base_price' => 1105,
                    'option_values' => ['Front Load', 'Up to 6 Kg'],
                    'source_column' => 'AD',
                ],
                [
                    'title' => 'Washing Machine | Front Load | 6.5-8 Kg',
                    'base_price' => 1122,
                    'option_values' => ['Front Load', '6.5-8 Kg'],
                    'source_column' => 'AE',
                ],
                [
                    'title' => 'Washing Machine | Front Load | 8.5+ Kg',
                    'base_price' => 1130.5,
                    'option_values' => ['Front Load', '8.5+ Kg'],
                    'source_column' => 'AF',
                ],
                [
                    'title' => 'Washing Machine | Top Load | Up to 6 Kg',
                    'base_price' => 867,
                    'option_values' => ['Top Load', 'Up to 6 Kg'],
                    'source_column' => 'AG',
                ],
                [
                    'title' => 'Washing Machine | Top Load | 6.5-8 Kg',
                    'base_price' => 884,
                    'option_values' => ['Top Load', '6.5-8 Kg'],
                    'source_column' => 'AH',
                ],
                [
                    'title' => 'Washing Machine | Top Load | 8.5+ Kg',
                    'base_price' => 892.5,
                    'option_values' => ['Top Load', '8.5+ Kg'],
                    'source_column' => 'AI',
                ],
                [
                    'title' => 'Washing Machine | Semi Automatic | Up to 6 Kg',
                    'base_price' => 850,
                    'option_values' => ['Semi Automatic', 'Up to 6 Kg'],
                    'source_column' => 'AJ',
                ],
                [
                    'title' => 'Washing Machine | Semi Automatic | 6.5-8 Kg',
                    'base_price' => 867,
                    'option_values' => ['Semi Automatic', '6.5-8 Kg'],
                    'source_column' => 'AK',
                ],
                [
                    'title' => 'Washing Machine | Semi Automatic | 8.5+ Kg',
                    'base_price' => 875.5,
                    'option_values' => ['Semi Automatic', '8.5+ Kg'],
                    'source_column' => 'AL',
                ],
            ],
            'Television' => [
                [
                    'title' => 'Television | Up to 32" | LED',
                    'base_price' => 210,
                    'option_values' => ['Up to 32"', 'LED'],
                    'source_column' => 'AM',
                ],
                [
                    'title' => 'Television | Up to 32" | LCD',
                    'base_price' => 192,
                    'option_values' => ['Up to 32"', 'LCD'],
                    'source_column' => 'AN',
                ],
                [
                    'title' => 'Television | Up to 32" | Plasma',
                    'base_price' => 188,
                    'option_values' => ['Up to 32"', 'Plasma'],
                    'source_column' => 'AO',
                ],
                [
                    'title' => 'Television | Up to 32" | CRT',
                    'base_price' => 188,
                    'option_values' => ['Up to 32"', 'CRT'],
                    'source_column' => 'AP',
                ],
                [
                    'title' => 'Television | 33-43" | LED',
                    'base_price' => 230,
                    'option_values' => ['33-43"', 'LED'],
                    'source_column' => 'AQ',
                ],
                [
                    'title' => 'Television | 33-43" | LCD',
                    'base_price' => 212,
                    'option_values' => ['33-43"', 'LCD'],
                    'source_column' => 'AR',
                ],
                [
                    'title' => 'Television | 33-43" | Plasma',
                    'base_price' => 208,
                    'option_values' => ['33-43"', 'Plasma'],
                    'source_column' => 'AS',
                ],
                [
                    'title' => 'Television | 33-43" | CRT',
                    'base_price' => 208,
                    'option_values' => ['33-43"', 'CRT'],
                    'source_column' => 'AT',
                ],
                [
                    'title' => 'Television | 44-55" | LED',
                    'base_price' => 260,
                    'option_values' => ['44-55"', 'LED'],
                    'source_column' => 'AU',
                ],
                [
                    'title' => 'Television | 44-55" | LCD',
                    'base_price' => 242,
                    'option_values' => ['44-55"', 'LCD'],
                    'source_column' => 'AV',
                ],
                [
                    'title' => 'Television | 44-55" | Plasma',
                    'base_price' => 238,
                    'option_values' => ['44-55"', 'Plasma'],
                    'source_column' => 'AW',
                ],
                [
                    'title' => 'Television | 44-55" | CRT',
                    'base_price' => 238,
                    'option_values' => ['44-55"', 'CRT'],
                    'source_column' => 'AX',
                ],
                [
                    'title' => 'Television | 56"+ | LED',
                    'base_price' => 310,
                    'option_values' => ['56"+', 'LED'],
                    'source_column' => 'AY',
                ],
                [
                    'title' => 'Television | 56"+ | LCD',
                    'base_price' => 292,
                    'option_values' => ['56"+', 'LCD'],
                    'source_column' => 'AZ',
                ],
                [
                    'title' => 'Television | 56"+ | Plasma',
                    'base_price' => 288,
                    'option_values' => ['56"+', 'Plasma'],
                    'source_column' => 'BA',
                ],
                [
                    'title' => 'Television | 56"+ | CRT',
                    'base_price' => 288,
                    'option_values' => ['56"+', 'CRT'],
                    'source_column' => 'BB',
                ],
            ],
            'Microwave' => [
                [
                    'title' => 'Microwave | Solo',
                    'base_price' => 300,
                    'option_values' => ['Solo'],
                    'source_column' => 'BC',
                ],
                [
                    'title' => 'Microwave | Grill',
                    'base_price' => 306,
                    'option_values' => ['Grill'],
                    'source_column' => 'BD',
                ],
                [
                    'title' => 'Microwave | Convection',
                    'base_price' => 312,
                    'option_values' => ['Convection'],
                    'source_column' => 'BE',
                ],
            ],
            'Refrigerator' => [
                [
                    'title' => 'Refrigerator | Single Door | Up to 200 L',
                    'base_price' => 882,
                    'option_values' => ['Single Door', 'Up to 200 L'],
                    'source_column' => 'BF',
                ],
                [
                    'title' => 'Refrigerator | Single Door | 201-300 L',
                    'base_price' => 909,
                    'option_values' => ['Single Door', '201-300 L'],
                    'source_column' => 'BG',
                ],
                [
                    'title' => 'Refrigerator | Single Door | 301-450 L',
                    'base_price' => 936,
                    'option_values' => ['Single Door', '301-450 L'],
                    'source_column' => 'BH',
                ],
                [
                    'title' => 'Refrigerator | Single Door | 450+ L',
                    'base_price' => 954,
                    'option_values' => ['Single Door', '450+ L'],
                    'source_column' => 'BI',
                ],
                [
                    'title' => 'Refrigerator | Double Door | Up to 200 L',
                    'base_price' => 900,
                    'option_values' => ['Double Door', 'Up to 200 L'],
                    'source_column' => 'BJ',
                ],
                [
                    'title' => 'Refrigerator | Double Door | 201-300 L',
                    'base_price' => 927,
                    'option_values' => ['Double Door', '201-300 L'],
                    'source_column' => 'BK',
                ],
                [
                    'title' => 'Refrigerator | Double Door | 301-450 L',
                    'base_price' => 954,
                    'option_values' => ['Double Door', '301-450 L'],
                    'source_column' => 'BL',
                ],
                [
                    'title' => 'Refrigerator | Double Door | 450+ L',
                    'base_price' => 972,
                    'option_values' => ['Double Door', '450+ L'],
                    'source_column' => 'BM',
                ],
                [
                    'title' => 'Refrigerator | Side-by-Side | Up to 200 L',
                    'base_price' => 927,
                    'option_values' => ['Side-by-Side', 'Up to 200 L'],
                    'source_column' => 'BN',
                ],
                [
                    'title' => 'Refrigerator | Side-by-Side | 201-300 L',
                    'base_price' => 954,
                    'option_values' => ['Side-by-Side', '201-300 L'],
                    'source_column' => 'BO',
                ],
                [
                    'title' => 'Refrigerator | Side-by-Side | 301-450 L',
                    'base_price' => 981,
                    'option_values' => ['Side-by-Side', '301-450 L'],
                    'source_column' => 'BP',
                ],
                [
                    'title' => 'Refrigerator | Side-by-Side | 450+ L',
                    'base_price' => 999,
                    'option_values' => ['Side-by-Side', '450+ L'],
                    'source_column' => 'BQ',
                ],
            ],
            'Mixer Grinder' => [
                [
                    'title' => 'Mixer Grinder | 1 Jar',
                    'base_price' => 127.4,
                    'option_values' => ['1 Jar'],
                    'source_column' => 'BR',
                ],
                [
                    'title' => 'Mixer Grinder | 2 Jars',
                    'base_price' => 130,
                    'option_values' => ['2 Jars'],
                    'source_column' => 'BS',
                ],
                [
                    'title' => 'Mixer Grinder | 3+ Jars',
                    'base_price' => 132.6,
                    'option_values' => ['3+ Jars'],
                    'source_column' => 'BT',
                ],
            ],
            'Kitchen Chimney' => [
                [
                    'title' => 'Kitchen Chimney | Below 1000 m3/h',
                    'base_price' => 196,
                    'option_values' => ['Below 1000 m3/h'],
                    'source_column' => 'BU',
                ],
                [
                    'title' => 'Kitchen Chimney | 1000-1200 m3/h',
                    'base_price' => 202,
                    'option_values' => ['1000-1200 m3/h'],
                    'source_column' => 'BV',
                ],
                [
                    'title' => 'Kitchen Chimney | 1200+ m3/h',
                    'base_price' => 206,
                    'option_values' => ['1200+ m3/h'],
                    'source_column' => 'BW',
                ],
            ],
            'Water Purifier' => [
                [
                    'title' => 'Water Purifier | RO',
                    'base_price' => 150,
                    'option_values' => ['RO'],
                    'source_column' => 'BX',
                ],
                [
                    'title' => 'Water Purifier | RO+UV',
                    'base_price' => 153,
                    'option_values' => ['RO+UV'],
                    'source_column' => 'BY',
                ],
                [
                    'title' => 'Water Purifier | RO+UV+UF',
                    'base_price' => 156,
                    'option_values' => ['RO+UV+UF'],
                    'source_column' => 'BZ',
                ],
            ],
            'Mobile Phone' => [
                [
                    'title' => 'Mobile Phone | 64 GB',
                    'base_price' => 145.5,
                    'option_values' => ['64 GB'],
                    'source_column' => 'CA',
                ],
                [
                    'title' => 'Mobile Phone | 128 GB',
                    'base_price' => 150,
                    'option_values' => ['128 GB'],
                    'source_column' => 'CB',
                ],
                [
                    'title' => 'Mobile Phone | 256 GB',
                    'base_price' => 156,
                    'option_values' => ['256 GB'],
                    'source_column' => 'CC',
                ],
                [
                    'title' => 'Mobile Phone | 512 GB',
                    'base_price' => 160.5,
                    'option_values' => ['512 GB'],
                    'source_column' => 'CD',
                ],
            ],
            'Laptop' => [
                [
                    'title' => 'Laptop | Intel i3 / Ryzen 3 | 1st-5th Gen',
                    'base_price' => 588,
                    'option_values' => ['Intel i3 / Ryzen 3', '1st-5th Gen'],
                    'source_column' => 'CE',
                ],
                [
                    'title' => 'Laptop | Intel i3 / Ryzen 3 | 6th-8th Gen',
                    'base_price' => 637,
                    'option_values' => ['Intel i3 / Ryzen 3', '6th-8th Gen'],
                    'source_column' => 'CF',
                ],
                [
                    'title' => 'Laptop | Intel i3 / Ryzen 3 | 9th-10th Gen',
                    'base_price' => 686,
                    'option_values' => ['Intel i3 / Ryzen 3', '9th-10th Gen'],
                    'source_column' => 'CG',
                ],
                [
                    'title' => 'Laptop | Intel i3 / Ryzen 3 | 11th-13th Gen',
                    'base_price' => 728,
                    'option_values' => ['Intel i3 / Ryzen 3', '11th-13th Gen'],
                    'source_column' => 'CH',
                ],
                [
                    'title' => 'Laptop | Intel i5 / Ryzen 5 | 1st-5th Gen',
                    'base_price' => 616,
                    'option_values' => ['Intel i5 / Ryzen 5', '1st-5th Gen'],
                    'source_column' => 'CI',
                ],
                [
                    'title' => 'Laptop | Intel i5 / Ryzen 5 | 6th-8th Gen',
                    'base_price' => 665,
                    'option_values' => ['Intel i5 / Ryzen 5', '6th-8th Gen'],
                    'source_column' => 'CJ',
                ],
                [
                    'title' => 'Laptop | Intel i5 / Ryzen 5 | 9th-10th Gen',
                    'base_price' => 714,
                    'option_values' => ['Intel i5 / Ryzen 5', '9th-10th Gen'],
                    'source_column' => 'CK',
                ],
                [
                    'title' => 'Laptop | Intel i5 / Ryzen 5 | 11th-13th Gen',
                    'base_price' => 756,
                    'option_values' => ['Intel i5 / Ryzen 5', '11th-13th Gen'],
                    'source_column' => 'CL',
                ],
                [
                    'title' => 'Laptop | Intel i7+ / Ryzen 7+ | 1st-5th Gen',
                    'base_price' => 672,
                    'option_values' => ['Intel i7+ / Ryzen 7+', '1st-5th Gen'],
                    'source_column' => 'CM',
                ],
                [
                    'title' => 'Laptop | Intel i7+ / Ryzen 7+ | 6th-8th Gen',
                    'base_price' => 721,
                    'option_values' => ['Intel i7+ / Ryzen 7+', '6th-8th Gen'],
                    'source_column' => 'CN',
                ],
                [
                    'title' => 'Laptop | Intel i7+ / Ryzen 7+ | 9th-10th Gen',
                    'base_price' => 770,
                    'option_values' => ['Intel i7+ / Ryzen 7+', '9th-10th Gen'],
                    'source_column' => 'CO',
                ],
                [
                    'title' => 'Laptop | Intel i7+ / Ryzen 7+ | 11th-13th Gen',
                    'base_price' => 812,
                    'option_values' => ['Intel i7+ / Ryzen 7+', '11th-13th Gen'],
                    'source_column' => 'CP',
                ],
                [
                    'title' => 'Laptop | Apple MacBook | Intel',
                    'base_price' => 784,
                    'option_values' => ['Apple MacBook', 'Intel'],
                    'source_column' => 'EW',
                ],
                [
                    'title' => 'Laptop | Apple MacBook | M1',
                    'base_price' => 1085,
                    'option_values' => ['Apple MacBook', 'M1'],
                    'source_column' => 'EX',
                ],
                [
                    'title' => 'Laptop | Apple MacBook | M2',
                    'base_price' => 1225,
                    'option_values' => ['Apple MacBook', 'M2'],
                    'source_column' => 'EY',
                ],
                [
                    'title' => 'Laptop | Apple MacBook | M1 Pro',
                    'base_price' => 1435,
                    'option_values' => ['Apple MacBook', 'M1 Pro'],
                    'source_column' => 'EZ',
                ],
                [
                    'title' => 'Laptop | Apple MacBook | M2 Pro',
                    'base_price' => 1575,
                    'option_values' => ['Apple MacBook', 'M2 Pro'],
                    'source_column' => 'FA',
                ],
                [
                    'title' => 'Laptop | Apple MacBook | M3 / M3 Pro',
                    'base_price' => 1715,
                    'option_values' => ['Apple MacBook', 'M3 / M3 Pro'],
                    'source_column' => 'FB',
                ],
            ],
            'Cables & Wires' => [
                [
                    'title' => 'Cables & Wires | High Copper',
                    'base_price' => 42.4,
                    'option_values' => ['High Copper'],
                    'source_column' => 'CQ',
                ],
                [
                    'title' => 'Cables & Wires | Mixed',
                    'base_price' => 40,
                    'option_values' => ['Mixed'],
                    'source_column' => 'CR',
                ],
                [
                    'title' => 'Cables & Wires | Low Copper',
                    'base_price' => 38,
                    'option_values' => ['Low Copper'],
                    'source_column' => 'CS',
                ],
            ],
            'CPU Cabinet' => [
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i3 / Ryzen 3 | 1st-5th Gen',
                    'base_price' => 454.5,
                    'option_values' => ['Branded Desktop', 'Intel i3 / Ryzen 3', '1st-5th Gen'],
                    'source_column' => 'CT',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i5 / Ryzen 5 | 1st-5th Gen',
                    'base_price' => 468,
                    'option_values' => ['Branded Desktop', 'Intel i5 / Ryzen 5', '1st-5th Gen'],
                    'source_column' => 'CU',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i7+ / Ryzen 7+ | 1st-5th Gen',
                    'base_price' => 499.5,
                    'option_values' => ['Branded Desktop', 'Intel i7+ / Ryzen 7+', '1st-5th Gen'],
                    'source_column' => 'CV',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i3 / Ryzen 3 | 1st-5th Gen',
                    'base_price' => 436.5,
                    'option_values' => ['Assembled Desktop', 'Intel i3 / Ryzen 3', '1st-5th Gen'],
                    'source_column' => 'CW',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i5 / Ryzen 5 | 1st-5th Gen',
                    'base_price' => 450,
                    'option_values' => ['Assembled Desktop', 'Intel i5 / Ryzen 5', '1st-5th Gen'],
                    'source_column' => 'CX',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i7+ / Ryzen 7+ | 1st-5th Gen',
                    'base_price' => 481.5,
                    'option_values' => ['Assembled Desktop', 'Intel i7+ / Ryzen 7+', '1st-5th Gen'],
                    'source_column' => 'CY',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i3 / Ryzen 3 | 1st-5th Gen',
                    'base_price' => 400.5,
                    'option_values' => ['Bare Cabinet', 'Intel i3 / Ryzen 3', '1st-5th Gen'],
                    'source_column' => 'CZ',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i5 / Ryzen 5 | 1st-5th Gen',
                    'base_price' => 414,
                    'option_values' => ['Bare Cabinet', 'Intel i5 / Ryzen 5', '1st-5th Gen'],
                    'source_column' => 'DA',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i7+ / Ryzen 7+ | 1st-5th Gen',
                    'base_price' => 445.5,
                    'option_values' => ['Bare Cabinet', 'Intel i7+ / Ryzen 7+', '1st-5th Gen'],
                    'source_column' => 'DB',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i3 / Ryzen 3 | 6th-8th Gen',
                    'base_price' => 490.86,
                    'option_values' => ['Branded Desktop', 'Intel i3 / Ryzen 3', '6th-8th Gen'],
                    'source_column' => 'FC',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i5 / Ryzen 5 | 6th-8th Gen',
                    'base_price' => 505.44,
                    'option_values' => ['Branded Desktop', 'Intel i5 / Ryzen 5', '6th-8th Gen'],
                    'source_column' => 'FD',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i7+ / Ryzen 7+ | 6th-8th Gen',
                    'base_price' => 539.46,
                    'option_values' => ['Branded Desktop', 'Intel i7+ / Ryzen 7+', '6th-8th Gen'],
                    'source_column' => 'FE',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i3 / Ryzen 3 | 6th-8th Gen',
                    'base_price' => 471.42,
                    'option_values' => ['Assembled Desktop', 'Intel i3 / Ryzen 3', '6th-8th Gen'],
                    'source_column' => 'FF',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i5 / Ryzen 5 | 6th-8th Gen',
                    'base_price' => 486,
                    'option_values' => ['Assembled Desktop', 'Intel i5 / Ryzen 5', '6th-8th Gen'],
                    'source_column' => 'FG',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i7+ / Ryzen 7+ | 6th-8th Gen',
                    'base_price' => 520.02,
                    'option_values' => ['Assembled Desktop', 'Intel i7+ / Ryzen 7+', '6th-8th Gen'],
                    'source_column' => 'FH',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i3 / Ryzen 3 | 6th-8th Gen',
                    'base_price' => 432.54,
                    'option_values' => ['Bare Cabinet', 'Intel i3 / Ryzen 3', '6th-8th Gen'],
                    'source_column' => 'FI',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i5 / Ryzen 5 | 6th-8th Gen',
                    'base_price' => 447.12,
                    'option_values' => ['Bare Cabinet', 'Intel i5 / Ryzen 5', '6th-8th Gen'],
                    'source_column' => 'FJ',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i7+ / Ryzen 7+ | 6th-8th Gen',
                    'base_price' => 481.14,
                    'option_values' => ['Bare Cabinet', 'Intel i7+ / Ryzen 7+', '6th-8th Gen'],
                    'source_column' => 'FK',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i3 / Ryzen 3 | 9th-10th Gen',
                    'base_price' => 527.22,
                    'option_values' => ['Branded Desktop', 'Intel i3 / Ryzen 3', '9th-10th Gen'],
                    'source_column' => 'FL',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i5 / Ryzen 5 | 9th-10th Gen',
                    'base_price' => 542.88,
                    'option_values' => ['Branded Desktop', 'Intel i5 / Ryzen 5', '9th-10th Gen'],
                    'source_column' => 'FM',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i7+ / Ryzen 7+ | 9th-10th Gen',
                    'base_price' => 579.42,
                    'option_values' => ['Branded Desktop', 'Intel i7+ / Ryzen 7+', '9th-10th Gen'],
                    'source_column' => 'FN',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i3 / Ryzen 3 | 9th-10th Gen',
                    'base_price' => 506.34,
                    'option_values' => ['Assembled Desktop', 'Intel i3 / Ryzen 3', '9th-10th Gen'],
                    'source_column' => 'FO',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i5 / Ryzen 5 | 9th-10th Gen',
                    'base_price' => 522,
                    'option_values' => ['Assembled Desktop', 'Intel i5 / Ryzen 5', '9th-10th Gen'],
                    'source_column' => 'FP',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i7+ / Ryzen 7+ | 9th-10th Gen',
                    'base_price' => 558.54,
                    'option_values' => ['Assembled Desktop', 'Intel i7+ / Ryzen 7+', '9th-10th Gen'],
                    'source_column' => 'FQ',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i3 / Ryzen 3 | 9th-10th Gen',
                    'base_price' => 464.58,
                    'option_values' => ['Bare Cabinet', 'Intel i3 / Ryzen 3', '9th-10th Gen'],
                    'source_column' => 'FR',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i5 / Ryzen 5 | 9th-10th Gen',
                    'base_price' => 480.24,
                    'option_values' => ['Bare Cabinet', 'Intel i5 / Ryzen 5', '9th-10th Gen'],
                    'source_column' => 'FS',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i7+ / Ryzen 7+ | 9th-10th Gen',
                    'base_price' => 516.78,
                    'option_values' => ['Bare Cabinet', 'Intel i7+ / Ryzen 7+', '9th-10th Gen'],
                    'source_column' => 'FT',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i3 / Ryzen 3 | 11th-13th Gen',
                    'base_price' => 563.58,
                    'option_values' => ['Branded Desktop', 'Intel i3 / Ryzen 3', '11th-13th Gen'],
                    'source_column' => 'FU',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i5 / Ryzen 5 | 11th-13th Gen',
                    'base_price' => 580.32,
                    'option_values' => ['Branded Desktop', 'Intel i5 / Ryzen 5', '11th-13th Gen'],
                    'source_column' => 'FV',
                ],
                [
                    'title' => 'CPU Cabinet | Branded Desktop | Intel i7+ / Ryzen 7+ | 11th-13th Gen',
                    'base_price' => 619.38,
                    'option_values' => ['Branded Desktop', 'Intel i7+ / Ryzen 7+', '11th-13th Gen'],
                    'source_column' => 'FW',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i3 / Ryzen 3 | 11th-13th Gen',
                    'base_price' => 541.26,
                    'option_values' => ['Assembled Desktop', 'Intel i3 / Ryzen 3', '11th-13th Gen'],
                    'source_column' => 'FX',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i5 / Ryzen 5 | 11th-13th Gen',
                    'base_price' => 558,
                    'option_values' => ['Assembled Desktop', 'Intel i5 / Ryzen 5', '11th-13th Gen'],
                    'source_column' => 'FY',
                ],
                [
                    'title' => 'CPU Cabinet | Assembled Desktop | Intel i7+ / Ryzen 7+ | 11th-13th Gen',
                    'base_price' => 597.06,
                    'option_values' => ['Assembled Desktop', 'Intel i7+ / Ryzen 7+', '11th-13th Gen'],
                    'source_column' => 'FZ',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i3 / Ryzen 3 | 11th-13th Gen',
                    'base_price' => 496.62,
                    'option_values' => ['Bare Cabinet', 'Intel i3 / Ryzen 3', '11th-13th Gen'],
                    'source_column' => 'GA',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i5 / Ryzen 5 | 11th-13th Gen',
                    'base_price' => 513.36,
                    'option_values' => ['Bare Cabinet', 'Intel i5 / Ryzen 5', '11th-13th Gen'],
                    'source_column' => 'GB',
                ],
                [
                    'title' => 'CPU Cabinet | Bare Cabinet | Intel i7+ / Ryzen 7+ | 11th-13th Gen',
                    'base_price' => 552.42,
                    'option_values' => ['Bare Cabinet', 'Intel i7+ / Ryzen 7+', '11th-13th Gen'],
                    'source_column' => 'GC',
                ],
            ],
            'Wooden Chair' => [
                [
                    'title' => 'Wooden Chair | Solid Wood | Small',
                    'base_price' => 156,
                    'option_values' => ['Solid Wood', 'Small'],
                    'source_column' => 'DC',
                ],
                [
                    'title' => 'Wooden Chair | Solid Wood | Medium',
                    'base_price' => 159,
                    'option_values' => ['Solid Wood', 'Medium'],
                    'source_column' => 'DD',
                ],
                [
                    'title' => 'Wooden Chair | Solid Wood | Large',
                    'base_price' => 163.5,
                    'option_values' => ['Solid Wood', 'Large'],
                    'source_column' => 'DE',
                ],
                [
                    'title' => 'Wooden Chair | Engineered Wood | Small',
                    'base_price' => 150,
                    'option_values' => ['Engineered Wood', 'Small'],
                    'source_column' => 'DF',
                ],
                [
                    'title' => 'Wooden Chair | Engineered Wood | Medium',
                    'base_price' => 153,
                    'option_values' => ['Engineered Wood', 'Medium'],
                    'source_column' => 'DG',
                ],
                [
                    'title' => 'Wooden Chair | Engineered Wood | Large',
                    'base_price' => 157.5,
                    'option_values' => ['Engineered Wood', 'Large'],
                    'source_column' => 'DH',
                ],
                [
                    'title' => 'Wooden Chair | Plastic | Small',
                    'base_price' => 144,
                    'option_values' => ['Plastic', 'Small'],
                    'source_column' => 'DI',
                ],
                [
                    'title' => 'Wooden Chair | Plastic | Medium',
                    'base_price' => 147,
                    'option_values' => ['Plastic', 'Medium'],
                    'source_column' => 'DJ',
                ],
                [
                    'title' => 'Wooden Chair | Plastic | Large',
                    'base_price' => 151.5,
                    'option_values' => ['Plastic', 'Large'],
                    'source_column' => 'DK',
                ],
                [
                    'title' => 'Wooden Chair | Metal | Small',
                    'base_price' => 148.5,
                    'option_values' => ['Metal', 'Small'],
                    'source_column' => 'DL',
                ],
                [
                    'title' => 'Wooden Chair | Metal | Medium',
                    'base_price' => 151.5,
                    'option_values' => ['Metal', 'Medium'],
                    'source_column' => 'DM',
                ],
                [
                    'title' => 'Wooden Chair | Metal | Large',
                    'base_price' => 156,
                    'option_values' => ['Metal', 'Large'],
                    'source_column' => 'DN',
                ],
                [
                    'title' => 'Wooden Chair | Other | Small',
                    'base_price' => 147,
                    'option_values' => ['Other', 'Small'],
                    'source_column' => 'DO',
                ],
                [
                    'title' => 'Wooden Chair | Other | Medium',
                    'base_price' => 150,
                    'option_values' => ['Other', 'Medium'],
                    'source_column' => 'DP',
                ],
                [
                    'title' => 'Wooden Chair | Other | Large',
                    'base_price' => 154.5,
                    'option_values' => ['Other', 'Large'],
                    'source_column' => 'DQ',
                ],
            ],
            'Steel Cupboard' => [
                [
                    'title' => 'Steel Cupboard | Heavy Steel | 2 Door Compact',
                    'base_price' => 525,
                    'option_values' => ['Heavy Steel', '2 Door Compact'],
                    'source_column' => 'DR',
                ],
                [
                    'title' => 'Steel Cupboard | Heavy Steel | 2 Door Standard',
                    'base_price' => 535,
                    'option_values' => ['Heavy Steel', '2 Door Standard'],
                    'source_column' => 'DS',
                ],
                [
                    'title' => 'Steel Cupboard | Heavy Steel | 3 Door / Large',
                    'base_price' => 560,
                    'option_values' => ['Heavy Steel', '3 Door / Large'],
                    'source_column' => 'DT',
                ],
                [
                    'title' => 'Steel Cupboard | Light Steel | 2 Door Compact',
                    'base_price' => 500,
                    'option_values' => ['Light Steel', '2 Door Compact'],
                    'source_column' => 'DU',
                ],
                [
                    'title' => 'Steel Cupboard | Light Steel | 2 Door Standard',
                    'base_price' => 510,
                    'option_values' => ['Light Steel', '2 Door Standard'],
                    'source_column' => 'DV',
                ],
                [
                    'title' => 'Steel Cupboard | Light Steel | 3 Door / Large',
                    'base_price' => 535,
                    'option_values' => ['Light Steel', '3 Door / Large'],
                    'source_column' => 'DW',
                ],
                [
                    'title' => 'Steel Cupboard | Wood + Steel Mix | 2 Door Compact',
                    'base_price' => 495,
                    'option_values' => ['Wood + Steel Mix', '2 Door Compact'],
                    'source_column' => 'DX',
                ],
                [
                    'title' => 'Steel Cupboard | Wood + Steel Mix | 2 Door Standard',
                    'base_price' => 505,
                    'option_values' => ['Wood + Steel Mix', '2 Door Standard'],
                    'source_column' => 'DY',
                ],
                [
                    'title' => 'Steel Cupboard | Wood + Steel Mix | 3 Door / Large',
                    'base_price' => 530,
                    'option_values' => ['Wood + Steel Mix', '3 Door / Large'],
                    'source_column' => 'DZ',
                ],
                [
                    'title' => 'Steel Cupboard | Other | 2 Door Compact',
                    'base_price' => 490,
                    'option_values' => ['Other', '2 Door Compact'],
                    'source_column' => 'EA',
                ],
                [
                    'title' => 'Steel Cupboard | Other | 2 Door Standard',
                    'base_price' => 500,
                    'option_values' => ['Other', '2 Door Standard'],
                    'source_column' => 'EB',
                ],
                [
                    'title' => 'Steel Cupboard | Other | 3 Door / Large',
                    'base_price' => 525,
                    'option_values' => ['Other', '3 Door / Large'],
                    'source_column' => 'EC',
                ],
            ],
            'Study Table' => [
                [
                    'title' => 'Study Table | Solid Wood | 2-3 ft',
                    'base_price' => 154.5,
                    'option_values' => ['Solid Wood', '2-3 ft'],
                    'source_column' => 'ED',
                ],
                [
                    'title' => 'Study Table | Solid Wood | 4 ft',
                    'base_price' => 157.5,
                    'option_values' => ['Solid Wood', '4 ft'],
                    'source_column' => 'EE',
                ],
                [
                    'title' => 'Study Table | Solid Wood | 5 ft+',
                    'base_price' => 163.5,
                    'option_values' => ['Solid Wood', '5 ft+'],
                    'source_column' => 'EF',
                ],
                [
                    'title' => 'Study Table | Engineered Wood | 2-3 ft',
                    'base_price' => 150,
                    'option_values' => ['Engineered Wood', '2-3 ft'],
                    'source_column' => 'EG',
                ],
                [
                    'title' => 'Study Table | Engineered Wood | 4 ft',
                    'base_price' => 153,
                    'option_values' => ['Engineered Wood', '4 ft'],
                    'source_column' => 'EH',
                ],
                [
                    'title' => 'Study Table | Engineered Wood | 5 ft+',
                    'base_price' => 159,
                    'option_values' => ['Engineered Wood', '5 ft+'],
                    'source_column' => 'EI',
                ],
                [
                    'title' => 'Study Table | Metal Frame | 2-3 ft',
                    'base_price' => 151.5,
                    'option_values' => ['Metal Frame', '2-3 ft'],
                    'source_column' => 'EJ',
                ],
                [
                    'title' => 'Study Table | Metal Frame | 4 ft',
                    'base_price' => 154.5,
                    'option_values' => ['Metal Frame', '4 ft'],
                    'source_column' => 'EK',
                ],
                [
                    'title' => 'Study Table | Metal Frame | 5 ft+',
                    'base_price' => 160.5,
                    'option_values' => ['Metal Frame', '5 ft+'],
                    'source_column' => 'EL',
                ],
                [
                    'title' => 'Study Table | Plastic | 2-3 ft',
                    'base_price' => 144,
                    'option_values' => ['Plastic', '2-3 ft'],
                    'source_column' => 'EM',
                ],
                [
                    'title' => 'Study Table | Plastic | 4 ft',
                    'base_price' => 147,
                    'option_values' => ['Plastic', '4 ft'],
                    'source_column' => 'EN',
                ],
                [
                    'title' => 'Study Table | Plastic | 5 ft+',
                    'base_price' => 153,
                    'option_values' => ['Plastic', '5 ft+'],
                    'source_column' => 'EO',
                ],
                [
                    'title' => 'Study Table | Other | 2-3 ft',
                    'base_price' => 147,
                    'option_values' => ['Other', '2-3 ft'],
                    'source_column' => 'EP',
                ],
                [
                    'title' => 'Study Table | Other | 4 ft',
                    'base_price' => 150,
                    'option_values' => ['Other', '4 ft'],
                    'source_column' => 'EQ',
                ],
                [
                    'title' => 'Study Table | Other | 5 ft+',
                    'base_price' => 156,
                    'option_values' => ['Other', '5 ft+'],
                    'source_column' => 'ER',
                ],
            ],
            'Sofa Set' => [
                [
                    'title' => 'Sofa Set | 1 Seater',
                    'base_price' => 288,
                    'option_values' => ['1 Seater'],
                    'source_column' => 'ES',
                ],
                [
                    'title' => 'Sofa Set | 2 Seater',
                    'base_price' => 300,
                    'option_values' => ['2 Seater'],
                    'source_column' => 'ET',
                ],
                [
                    'title' => 'Sofa Set | 3 Seater',
                    'base_price' => 312,
                    'option_values' => ['3 Seater'],
                    'source_column' => 'EU',
                ],
                [
                    'title' => 'Sofa Set | L-Shape / 5 Seater+',
                    'base_price' => 324,
                    'option_values' => ['L-Shape / 5 Seater+'],
                    'source_column' => 'EV',
                ],
            ],
        ];

        foreach ($variantPricingRules as $categoryName => $rules) {
            /** @var Category|null $category */
            $category = $seededSubCategoriesByName[$categoryName] ?? null;
            if (!$category) {
                continue;
            }

            $activeKeys = [];
            foreach ($rules as $rule) {
                $variantKey = Str::slug($rule['title']);
                $activeKeys[] = $variantKey;

                PricingVariantRule::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'variant_key' => $variantKey,
                    ],
                    [
                        'title' => $rule['title'],
                        'option_values' => $rule['option_values'],
                        'base_price' => $rule['base_price'],
                        'source_column' => $rule['source_column'],
                        'status' => true,
                    ]
                );
            }

            PricingVariantRule::where('category_id', $category->id)
                ->whereNotIn('variant_key', $activeKeys)
                ->delete();
        }
    }
}
