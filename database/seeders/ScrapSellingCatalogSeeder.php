<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\CategoryType;
use App\Models\PricingRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ScrapSellingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $hasAdjustmentType = Schema::hasColumn('pricing_rules', 'adjustment_type');
        $hasAdjustmentValue = Schema::hasColumn('pricing_rules', 'adjustment_value');

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
                    // Legacy appliance + gadget flow
                    ['name' => 'Air Conditioner', 'base_price' => 2200, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/air_conditioner.jpg'],
                    ['name' => 'Washing Machine', 'base_price' => 1200, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/washing_machine.jpg'],
                    ['name' => 'Television', 'base_price' => 600, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/television.jpg'],
                    ['name' => 'Microwave', 'base_price' => 450, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/microwave.jpg'],
                    ['name' => 'Refrigerator', 'base_price' => 2400, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/refrigerator.jpg'],
                    ['name' => 'Mixer Grinder', 'base_price' => 220, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/mixer_grinder.jpg'],
                    ['name' => 'Kitchen Chimney', 'base_price' => 750, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/kitchen_chimney.jpg'],
                    ['name' => 'Water Purifier', 'base_price' => 350, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/water_purifier.jpg'],
                    ['name' => 'Mobile Phone', 'base_price' => 500, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/mobile_phone.jpg'],
                    ['name' => 'Laptop', 'base_price' => 2400, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/laptop.jpg'],
                    ['name' => 'Cables & Wires', 'base_price' => 40, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/cables_wires.jpg'],
                    ['name' => 'CPU Cabinet', 'base_price' => 350, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/cpu_cabinet.jpg'],
                    // Additional e-waste items from XLS
                    ['name' => 'Desktop Computer', 'base_price' => 1800, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/desktop_computer.jpg'],
                    ['name' => 'CRT Monitor', 'base_price' => 300, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/crt_monitor.jpg'],
                    ['name' => 'LCD Monitor', 'base_price' => 700, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/lcd_monitor.jpg'],
                    ['name' => 'LED Monitor', 'base_price' => 950, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/led_monitor.jpg'],
                    ['name' => 'Mouse', 'base_price' => 40, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/mouse.jpg'],
                    ['name' => 'Keyboard', 'base_price' => 60, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/keyboard.jpg'],
                    ['name' => 'Motherboard', 'base_price' => 450, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/motherboard.jpg'],
                    ['name' => 'Hard Disk Drive', 'base_price' => 220, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/hard_disk_drive.jpg'],
                    ['name' => 'Server', 'base_price' => 3500, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/server.jpg'],
                    ['name' => 'RAM', 'base_price' => 120, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/ram.jpg'],
                    ['name' => 'Printer', 'base_price' => 700, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/printer.jpg'],
                    ['name' => 'Scanner', 'base_price' => 550, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/scanner.jpg'],
                    ['name' => 'Tablet', 'base_price' => 700, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/tablet.jpg'],
                    ['name' => 'Charger', 'base_price' => 60, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/charger.jpg'],
                    ['name' => 'Adapter', 'base_price' => 60, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/adapter.jpg'],
                    ['name' => 'Power Bank', 'base_price' => 180, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/power_bank.jpg'],
                    ['name' => 'Earphones', 'base_price' => 50, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/earphones.jpg'],
                    ['name' => 'Headphones', 'base_price' => 120, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/headphones.jpg'],
                ],
            ],
            'Metals, Power & Energy Hub' => [
                'image' => 'images/new/categories/cat_metals.jpg',
                'subcategories' => [
                    ['name' => 'MS Scrap', 'base_price' => 36, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/ms_scrap.jpg'],
                    ['name' => 'Cast Iron Scrap', 'base_price' => 34, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/cast_iron_scrap.jpg'],
                    ['name' => 'Heavy Melting Scrap', 'base_price' => 37, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/heavy_melting_scrap.jpg'],
                    ['name' => 'Iron Rod / Saria Scrap', 'base_price' => 38, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/iron_rod_saria.jpg'],
                    ['name' => 'Old Steel Pipes & Plates', 'base_price' => 35, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/old_steel_pipes_plates.jpg'],
                    ['name' => 'Machinery Iron Parts', 'base_price' => 33, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/machinery_iron_parts.jpg'],
                    ['name' => 'Copper Wire', 'base_price' => 520, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/copper_wire.jpg'],
                    ['name' => 'Aluminium Scrap', 'base_price' => 160, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/aluminium_scrap.jpg'],
                    ['name' => 'Lead Scrap', 'base_price' => 120, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/lead_scrap.jpg'],
                    ['name' => 'Zinc Scrap', 'base_price' => 150, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/zinc_scrap.jpg'],
                    ['name' => 'Nickel Scrap', 'base_price' => 220, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/nickel_scrap.jpg'],
                    ['name' => 'CNC Cutting Scrap', 'base_price' => 42, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/cnc_cutting_scrap.jpg'],
                    ['name' => 'Punching Scrap', 'base_price' => 40, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/punching_scrap.jpg'],
                    ['name' => 'Metal Turning (Boring Scrap)', 'base_price' => 39, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/metal_turning_boring.jpg'],
                    ['name' => 'Fabrication Waste', 'base_price' => 34, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/fabrication_waste.jpg'],
                    ['name' => 'Iron Nails', 'base_price' => 38, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/iron_nails.jpg'],
                    ['name' => 'Battery', 'base_price' => 120, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/battery.jpg'],
                ],
            ],
            'Plastic Scrap Categories' => [
                'image' => 'images/new/categories/cat_plastic.jpg',
                'subcategories' => [
                    ['name' => 'Water Bottles', 'base_price' => 14, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/water_bottles.jpg'],
                    ['name' => 'Soft Drink Bottles', 'base_price' => 14, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/soft_drink_bottles.jpg'],
                    ['name' => 'Transparent Oil Bottles', 'base_price' => 13, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/transparent_oil_bottles.jpg'],
                    ['name' => 'Detergent Bottles', 'base_price' => 16, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/detergent_bottles.jpg'],
                    ['name' => 'Chemical Cans', 'base_price' => 15, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/chemical_cans.jpg'],
                    ['name' => 'Plastic Drums', 'base_price' => 17, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/plastic_drums.jpg'],
                    ['name' => 'Pipes', 'base_price' => 12, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/pipes.jpg'],
                    ['name' => 'Wire Insulations', 'base_price' => 11, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/wire_insulations.jpg'],
                    ['name' => 'Flex Sheets', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/flex_sheets.jpg'],
                    ['name' => 'Carry Bags', 'base_price' => 9, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/carry_bags.jpg'],
                    ['name' => 'Packaging Films', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/packaging_films.jpg'],
                    ['name' => 'Stretch Wrap', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/stretch_wrap.jpg'],
                    ['name' => 'Plastic Crates', 'base_price' => 18, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/plastic_crates.jpg'],
                    ['name' => 'Plastic Chairs', 'base_price' => 16, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/plastic_chairs.jpg'],
                    ['name' => 'Battery Boxes', 'base_price' => 17, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/battery_boxes.jpg'],
                    ['name' => 'Thermocol', 'base_price' => 8, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/thermocol.jpg'],
                    ['name' => 'Disposable Cups', 'base_price' => 9, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/disposable_cups.jpg'],
                    ['name' => 'Foam Packaging', 'base_price' => 8, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/foam_packaging.jpg'],
                ],
            ],
            'Paper, Plastic & Glass Recyclables' => [
                'image' => 'images/new/categories/cat_paper_glass.jpg',
                'subcategories' => [
                    ['name' => 'Newspaper', 'base_price' => 16, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/newspaper.jpg'],
                    ['name' => 'Cardboard', 'base_price' => 12, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/cardboard.jpg'],
                    ['name' => 'Plastic Bottles', 'base_price' => 14, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/water_bottles.jpg'],
                    ['name' => 'Glass Bottles', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/glass_bottles.jpg'],
                    ['name' => 'White Record Paper', 'base_price' => 18, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/white_record_paper.jpg'],
                    ['name' => 'Office Paper Scrap', 'base_price' => 17, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/office_paper_scrap.jpg'],
                    ['name' => 'Mixed Paper', 'base_price' => 13, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/mixed_paper.jpg'],
                    ['name' => 'Books Scrap', 'base_price' => 11, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/books_scrap.jpg'],
                    ['name' => 'Notebook Scrap', 'base_price' => 11, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/notebook_scrap.jpg'],
                    ['name' => 'Brown Corrugated Carton Scrap', 'base_price' => 12, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/brown_corrugated_carton.jpg'],
                    ['name' => 'Duplex Board Carton Scrap', 'base_price' => 11, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/duplex_board_carton.jpg'],
                    ['name' => 'Corrugated Sheet / Punching Waste', 'base_price' => 10, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/corrugated_sheet_punching.jpg'],
                ],
            ],
            'Old Furniture' => [
                'image' => 'images/new/categories/cat_furniture.jpg',
                'subcategories' => [
                    ['name' => 'Wooden Chair', 'base_price' => 350, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/wooden_chair.jpg'],
                    ['name' => 'Steel Cupboard', 'base_price' => 1800, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/steel_cupboard.jpg'],
                    ['name' => 'Study Table', 'base_price' => 900, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/study_table.jpg'],
                    ['name' => 'Sofa Set', 'base_price' => 1400, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/sofa_set.jpg'],
                    ['name' => 'Bed', 'base_price' => 1600, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/bed.jpg'],
                    ['name' => 'Dressing Table', 'base_price' => 850, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/dressing_table.jpg'],
                    ['name' => 'Dining Table', 'base_price' => 1300, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/dining_table.jpg'],
                    ['name' => 'Work Stations', 'base_price' => 1200, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/work_stations.jpg'],
                    ['name' => 'Reception Table', 'base_price' => 1500, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/reception_table.jpg'],
                    ['name' => 'Boss Chair', 'base_price' => 700, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/boss_chair.jpg'],
                    ['name' => 'Settee Sofa', 'base_price' => 1100, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/settee_sofa.jpg'],
                ],
            ],
            'Hazardous Waste' => [
                'image' => 'images/new/categories/cat_hazardous.jpg',
                'subcategories' => [
                    ['name' => 'Lithium-Ion Battery', 'base_price' => 180, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/lithium_ion_battery.jpg'],
                    ['name' => 'Inverter Battery', 'base_price' => 105, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/inverter_battery.jpg'],
                    ['name' => 'Used Oil', 'base_price' => 30, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/used_oil.jpg'],
                    ['name' => 'Lead', 'base_price' => 120, 'pricing_type' => 'per_kg', 'image' => 'images/new/scrap/lead_scrap.jpg'],
                    ['name' => 'CFL Bulb', 'base_price' => 8, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/cfl_bulb.jpg'],
                    ['name' => 'Tube Light', 'base_price' => 12, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/tube_light.jpg'],
                    ['name' => 'Bulb', 'base_price' => 6, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/bulb.jpg'],
                ],
            ],
            'Vehicle & Machinery Waste' => [
                'image' => 'images/new/categories/cat_vehicles.jpg',
                'subcategories' => [
                    ['name' => 'Scooty', 'base_price' => 3500, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/scooty.jpg'],
                    ['name' => 'Bike', 'base_price' => 5500, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/bike.jpg'],
                    ['name' => 'Car', 'base_price' => 22000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/car.jpg'],
                    ['name' => 'Tata Ace', 'base_price' => 28000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/tata_ace.jpg'],
                    ['name' => 'Pick Bolero', 'base_price' => 32000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/pick_bolero.jpg'],
                    ['name' => 'Tata 407', 'base_price' => 50000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/tata_407.jpg'],
                    ['name' => 'Bus', 'base_price' => 80000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/bus.jpg'],
                    ['name' => 'Truck', 'base_price' => 120000, 'pricing_type' => 'per_piece', 'image' => 'images/new/scrap/truck.jpg'],
                ],
            ],
        ];

        // Standard base price matrix for all 103 subcategories.
        $standardBasePrices = [
            'Air Conditioner' => 2200,
            'Washing Machine' => 1200,
            'Television' => 600,
            'Microwave' => 450,
            'Refrigerator' => 2400,
            'Mixer Grinder' => 220,
            'Kitchen Chimney' => 750,
            'Water Purifier' => 350,
            'Mobile Phone' => 500,
            'Laptop' => 2400,
            'Cables & Wires' => 40,
            'CPU Cabinet' => 350,
            'Desktop Computer' => 1800,
            'CRT Monitor' => 300,
            'LCD Monitor' => 700,
            'LED Monitor' => 950,
            'Mouse' => 40,
            'Keyboard' => 60,
            'Motherboard' => 450,
            'Hard Disk Drive' => 220,
            'Server' => 3500,
            'RAM' => 120,
            'Printer' => 700,
            'Scanner' => 550,
            'Tablet' => 700,
            'Charger' => 60,
            'Adapter' => 60,
            'Power Bank' => 180,
            'Earphones' => 50,
            'Headphones' => 120,
            'MS Scrap' => 36,
            'Cast Iron Scrap' => 34,
            'Heavy Melting Scrap' => 37,
            'Iron Rod / Saria Scrap' => 38,
            'Old Steel Pipes & Plates' => 35,
            'Machinery Iron Parts' => 33,
            'Copper Wire' => 520,
            'Aluminium Scrap' => 160,
            'Lead Scrap' => 120,
            'Zinc Scrap' => 150,
            'Nickel Scrap' => 220,
            'CNC Cutting Scrap' => 42,
            'Punching Scrap' => 40,
            'Metal Turning (Boring Scrap)' => 39,
            'Fabrication Waste' => 34,
            'Iron Nails' => 38,
            'Battery' => 120,
            'Water Bottles' => 14,
            'Soft Drink Bottles' => 14,
            'Transparent Oil Bottles' => 13,
            'Detergent Bottles' => 16,
            'Chemical Cans' => 15,
            'Plastic Drums' => 17,
            'Pipes' => 12,
            'Wire Insulations' => 11,
            'Flex Sheets' => 10,
            'Carry Bags' => 9,
            'Packaging Films' => 10,
            'Stretch Wrap' => 10,
            'Plastic Crates' => 18,
            'Plastic Chairs' => 16,
            'Battery Boxes' => 17,
            'Thermocol' => 8,
            'Disposable Cups' => 9,
            'Foam Packaging' => 8,
            'Newspaper' => 16,
            'Cardboard' => 12,
            'Plastic Bottles' => 14,
            'Glass Bottles' => 10,
            'White Record Paper' => 18,
            'Office Paper Scrap' => 17,
            'Mixed Paper' => 13,
            'Books Scrap' => 11,
            'Notebook Scrap' => 11,
            'Brown Corrugated Carton Scrap' => 12,
            'Duplex Board Carton Scrap' => 11,
            'Corrugated Sheet / Punching Waste' => 10,
            'Wooden Chair' => 350,
            'Steel Cupboard' => 1800,
            'Study Table' => 900,
            'Sofa Set' => 1400,
            'Bed' => 1600,
            'Dressing Table' => 850,
            'Dining Table' => 1300,
            'Work Stations' => 1200,
            'Reception Table' => 1500,
            'Boss Chair' => 700,
            'Settee Sofa' => 1100,
            'Lithium-Ion Battery' => 180,
            'Inverter Battery' => 105,
            'Used Oil' => 30,
            'Lead' => 120,
            'CFL Bulb' => 8,
            'Tube Light' => 12,
            'Bulb' => 6,
            'Scooty' => 3500,
            'Bike' => 5500,
            'Car' => 22000,
            'Tata Ace' => 28000,
            'Pick Bolero' => 32000,
            'Tata 407' => 50000,
            'Bus' => 80000,
            'Truck' => 120000,
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
            'Television',
            'Washing Machine',
            'Refrigerator',
            'Microwave',
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
            'Bed',
            'Dressing Table',
            'Dining Table',
            'Work Stations',
            'Reception Table',
            'Boss Chair',
            'Settee Sofa',
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
                    [
                        'name' => ['en' => $sub['name'], 'hi' => $sub['name']],
                        'status' => true,
                        'image_path' => $sub['image'],
                    ]
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
                    'Cooling Capacity (Ton)' => ['0.8-1 Ton', '1.5 Ton', '2 Ton', 'Industrial 3 Ton+'],
                    'Working Condition' => ['Working', 'Non-Working', 'Scrap'],
                    'Usage Age (If Working)' => ['0-3 Years', '3-6 Years', '6+ Years'],
                ],
                'adjustments' => [
                     'Brand' => ['Daikin' => 3, 'Voltas' => 2, 'LG' => 2, 'Samsung' => 2, 'Blue Star' => 1, 'Hitachi' => 2, 'Other' => 0],
                    'Cooling Capacity (Ton)' => ['0.8-1 Ton' => -2, '1.5 Ton' => 2, '2 Ton' => 5, 'Industrial 3 Ton+' => 9],
                    'Working Condition' => ['Working' => 4, 'Non-Working' => -4, 'Scrap' => -8],
                    'Usage Age (If Working)' => ['0-3 Years' => 3, '3-6 Years' => 0, '6+ Years' => -3],
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
                     'Brand' => ['Samsung' => 2, 'LG' => 2, 'Sony' => 3, 'Mi' => 1, 'TCL' => 1, 'Panasonic' => 1, 'Other' => 0],
                    'Screen Size (Inch)' => ['Up to 32"' => -2, '33-43"' => 1, '44-55"' => 4, '56"+' => 7],
                    'Display Type' => ['LED' => 4, 'LCD' => 2, 'Plasma' => -2, 'CRT' => -6],
                    'Working Condition' => ['Working' => 4, 'Non-Working' => -4, 'Scrap' => -8],
                    'Usage Age (If Working)' => ['0-3 Years' => 3, '3-6 Years' => 0, '6+ Years' => -3],
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
                     'Brand' => ['LG' => 2, 'Samsung' => 2, 'Whirlpool' => 1, 'Bosch' => 3, 'IFB' => 2, 'Godrej' => 1, 'Other' => 0],
                    'Drum Capacity (Kg)' => ['Up to 6 Kg' => -2, '6.5-8 Kg' => 2, '8.5+ Kg' => 4],
                    'Machine Type' => ['Front Load' => 4, 'Top Load' => 2, 'Semi Automatic' => -2],
                    'Working Condition' => ['Working' => 4, 'Non-Working' => -4, 'Scrap' => -8],
                    'Usage Age (If Working)' => ['0-3 Years' => 3, '3-6 Years' => 0, '6+ Years' => -3],
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
                     'Brand' => ['LG' => 2, 'Samsung' => 2, 'Whirlpool' => 1, 'Godrej' => 1, 'Haier' => 1, 'Panasonic' => 1, 'Other' => 0],
                    'Capacity' => ['Up to 200 L' => -2, '201-300 L' => 1, '301-450 L' => 4, '450+ L' => 6],
                    'Door Type' => ['Single Door' => 0, 'Double Door' => 2, 'Side-by-Side' => 5],
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
                     'Brand' => ['LG' => 2, 'Samsung' => 2, 'IFB' => 1, 'Panasonic' => 1, 'Whirlpool' => 1, 'Other' => 0],
                    'Type' => ['Solo' => 0, 'Grill' => 2, 'Convection' => 4],
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
                    'Processor Tier' => ['Intel i3 / Ryzen 3', 'Intel i5 / Ryzen 5', 'Intel i7+ / Ryzen 7+'],
                    'Processor Generation' => ['5th Gen or Older', '6th-8th Gen', '9th-11th Gen', '12th Gen+'],
                    'RAM Variant' => ['4 GB', '8 GB', '16 GB', '32 GB+'],
                    'Storage Type' => ['HDD', 'SSD SATA', 'NVMe SSD'],
                    'Body Condition' => ['Good', 'Minor Dents', 'Major Damage'],
                    'Battery Health' => ['Good', 'Average', 'Poor'],
                    'Functional Status' => ['Fully Working', 'Partially Working', 'Not Working'],
                ],
                'adjustments' => [
                    'Brand' => ['Apple' => 12, 'Dell' => 3, 'HP' => 2, 'Lenovo' => 2, 'Asus' => 2, 'Acer' => 1, 'MSI' => 3, 'Other' => 0],
                    'Processor Tier' => ['Intel i3 / Ryzen 3' => -4, 'Intel i5 / Ryzen 5' => 0, 'Intel i7+ / Ryzen 7+' => 8],
                    'Processor Generation' => ['5th Gen or Older' => -12, '6th-8th Gen' => -5, '9th-11th Gen' => 2, '12th Gen+' => 8],
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
                    'Processor Generation' => ['5th Gen or Older', '6th-8th Gen', '9th-11th Gen', '12th Gen+'],
                    'RAM Installed' => ['No RAM', '4-8 GB', '16 GB+'],
                    'Storage Installed' => ['No Storage', 'HDD', 'SSD / NVMe'],
                    'Functional Status' => ['Fully Working', 'Partially Working', 'Not Working'],
                ],
                'adjustments' => [
                    'Cabinet Type' => ['Branded Desktop' => 4, 'Assembled Desktop' => 0, 'Bare Cabinet' => -8],
                    'Processor Tier' => ['Intel i3 / Ryzen 3' => -3, 'Intel i5 / Ryzen 5' => 0, 'Intel i7+ / Ryzen 7+' => 7],
                    'Processor Generation' => ['5th Gen or Older' => -10, '6th-8th Gen' => -4, '9th-11th Gen' => 2, '12th Gen+' => 7],
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

                foreach ($sectionOptions as $index => $optionText) {
                    $option = AttributeOption::updateOrCreate(
                        ['attribute_id' => $attribute->id, 'value->en' => $optionText],
                        [
                            'value' => ['en' => $optionText, 'hi' => $optionText],
                            'sort_order' => $index,
                        ]
                    );

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

            $syncPayload = [];
            foreach ($attributeIds as $attributeId) {
                $syncPayload[$attributeId] = ['is_required' => true];
            }
            $category->attributes()->sync($syncPayload);
        }
    }
}
