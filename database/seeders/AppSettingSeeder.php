<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Seeder;

class AppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Feature Flags
        AppSetting::set('donation_enabled', true, 'boolean', 'features');
        AppSetting::set('scrap_pickup_enabled', true, 'boolean', 'features');
        AppSetting::set('wallet_enabled', false, 'boolean', 'features');

        // General Settings
        AppSetting::set('customer_support_number', '+91 98702 91813', 'string', 'general');
        AppSetting::set('support_phone', '+91 11 3574 8627', 'string', 'general');
        AppSetting::set('default_city_id', 1, 'integer', 'general');
        AppSetting::set('minimum_free_pickup_amount', 1500, 'integer', 'general');
        AppSetting::set('low_value_shipping_charge', 100, 'integer', 'general');
        AppSetting::set('app_version', '1.0.3', 'string', 'general');
        AppSetting::set('donation_products', ['Cloth', 'Shoes', 'Toys', 'Books'], 'json', 'general');
        AppSetting::set('corporate_categories', ['E-Waste, Electrical & Digital Devices', 'Metals, Power & Energy Hub', 'Old Furniture'], 'json', 'general');
        AppSetting::set('corporate_meeting_types', ['in_person', 'google_meet', 'skype'], 'json', 'general');
        AppSetting::set('scrap_proof_images_required', true, 'boolean', 'general');
        AppSetting::set('scrap_proof_image_labels', ['front', 'back', 'left', 'right'], 'json', 'general');

        // Localization
        AppSetting::set('supported_languages', ['en', 'hi', 'gu'], 'json', 'localization');
    }
}
