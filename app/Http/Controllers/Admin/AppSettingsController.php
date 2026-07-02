<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\CategoryType;
use App\Models\HomeBanner;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppSettingsController extends Controller
{
    public function index()
    {
        $corporateCategories = CategoryType::where('status', true)
            ->where('show_in_corporate_booking', true)
            ->orderBy('id')
            ->get()
            ->map(fn(CategoryType $type) => $type->getTranslatedName())
            ->values()
            ->all();

        // Flatten for easy access with defaults
        $flat = [
            // Feature Flags
            'donation_enabled' => AppSetting::get('donation_enabled', true),
            'scrap_pickup_enabled' => AppSetting::get('scrap_pickup_enabled', true),
            'wallet_enabled' => AppSetting::get('wallet_enabled', false),
            'reschedule_enabled' => AppSetting::get('reschedule_enabled', true),
            'verification_required' => AppSetting::get('verification_required', true),
            'manual_item_add_edit_enabled' => AppSetting::get('manual_item_add_edit_enabled', true),
            'bill_generation_enabled' => AppSetting::get('bill_generation_enabled', true),
            'qr_verification_enabled' => AppSetting::get('qr_verification_enabled', true),

            // App Info & Update
            'app_version'   => AppSetting::get('app_version',   '1.0.3'),
            'latest_version'=> AppSetting::get('latest_version', '2.0.0'),
            'min_version'   => AppSetting::get('min_version',    '1.0.0'),
            'force_update'  => (bool) AppSetting::get('force_update', false),
            'android_url'   => AppSetting::get('android_url', 'https://play.google.com/store/apps/details?id=com.abhyuthanam.scrapify'),
            'ios_url'       => AppSetting::get('ios_url',     'https://apps.apple.com/us/app/scrapify/id6775160804'),
            'customer_support_number' => AppSetting::get('customer_support_number', '+91 00000 00000'),
            'support_phone' => AppSetting::get('support_phone', '+91 00000 00000'),
            'feedback_url' => AppSetting::get('feedback_url', 'https://scrapify.in/feedback'),
            'default_city_id' => AppSetting::get('default_city_id', 1),
            'minimum_free_pickup_amount' => AppSetting::get('minimum_free_pickup_amount', 1500),
            'low_value_shipping_charge' => AppSetting::get('low_value_shipping_charge', 100),
            'warehouse_service_pincodes_limit' => AppSetting::get('warehouse_service_pincodes_limit', 10),
            'donation_products' => AppSetting::get('donation_products', ['Cloth', 'Shoes', 'Toys', 'Books']),
            'corporate_categories' => $corporateCategories,
            'corporate_meeting_types' => AppSetting::get('corporate_meeting_types', ['in_person', 'google_meet', 'skype']),
            'scrap_proof_images_required' => AppSetting::get('scrap_proof_images_required', true),
            'scrap_proof_image_labels' => AppSetting::get('scrap_proof_image_labels', ['front', 'back', 'left', 'right']),

            // Intervals
            'pickup_boy_location_interval_seconds' => AppSetting::get('pickup_boy_location_interval_seconds', 30),
            'tracking_refresh_interval_seconds' => AppSetting::get('tracking_refresh_interval_seconds', 20),
            'dashboard_refresh_interval_seconds' => AppSetting::get('dashboard_refresh_interval_seconds', 60),
            'max_reschedule_hours_before_slot' => AppSetting::get('max_reschedule_hours_before_slot', 2),

            // MSG91 Settings
            'msg91_auth_key' => AppSetting::get('msg91_auth_key', config('services.msg91.auth_key')),
            'msg91_otp_template_id' => AppSetting::get('msg91_otp_template_id', config('services.msg91.otp_template_id')),
            'msg91_sms_template_id' => AppSetting::get('msg91_sms_template_id', config('services.msg91.sms_template_id')),
            'msg91_pickup_booked_template_id' => AppSetting::get('msg91_pickup_booked_template_id', ''),
            'msg91_pickup_completed_template_id' => AppSetting::get('msg91_pickup_completed_template_id', ''),
            'msg91_payment_feedback_template_id' => AppSetting::get('msg91_payment_feedback_template_id', ''),
            'msg91_pickup_rescheduled_template_id' => AppSetting::get('msg91_pickup_rescheduled_template_id', ''),
            'msg91_sender_id' => AppSetting::get('msg91_sender_id', config('services.msg91.sender_id', 'SCRPI')),
            'msg91_country_code' => AppSetting::get('msg91_country_code', config('services.msg91.country_code', '91')),

            // Languages
            'supported_languages' => AppSetting::get('supported_languages', ['en', 'hi', 'gu']),
        ];

        return Inertia::render('Admin/AppSettings/Index', [
            'settings' => $flat,
            'homeBanners' => HomeBanner::orderBy('sort_order')->get(['id', 'image_path', 'text', 'sort_order']),
        ]);
    }

    public function update(Request $request)
    {
        $booleanFields = [
            'donation_enabled',
            'scrap_pickup_enabled',
            'wallet_enabled',
            'reschedule_enabled',
            'verification_required',
            'manual_item_add_edit_enabled',
            'bill_generation_enabled',
            'qr_verification_enabled',
            'scrap_proof_images_required',
            'force_update',
        ];

        $intFields = [
            'pickup_boy_location_interval_seconds',
            'tracking_refresh_interval_seconds',
            'dashboard_refresh_interval_seconds',
            'max_reschedule_hours_before_slot',
            'default_city_id',
            'minimum_free_pickup_amount',
            'low_value_shipping_charge',
            'warehouse_service_pincodes_limit',
        ];

        $stringFields = [
            'app_version',
            'latest_version',
            'min_version',
            'android_url',
            'ios_url',
            'customer_support_number',
            'support_phone',
            'feedback_url',
            'msg91_auth_key',
            'msg91_otp_template_id',
            'msg91_sms_template_id',
            'msg91_pickup_booked_template_id',
            'msg91_pickup_completed_template_id',
            'msg91_payment_feedback_template_id',
            'msg91_pickup_rescheduled_template_id',
            'msg91_sender_id',
            'msg91_country_code',
        ];

        $jsonFields = [
            'donation_products',
            'corporate_meeting_types',
            'scrap_proof_image_labels',
        ];

        foreach ($booleanFields as $key) {
            if ($request->has($key)) {
                AppSetting::set($key, $request->boolean($key) ? '1' : '0', 'boolean', 'features');
            }
        }

        foreach ($intFields as $key) {
            if ($request->has($key)) {
                AppSetting::set($key, (int) $request->input($key), 'integer', 'settings');
            }
        }

        foreach ($stringFields as $key) {
            if ($request->has($key)) {
                AppSetting::set($key, $request->input($key), 'string', 'settings');
            }
        }

        foreach ($jsonFields as $key) {
            if ($request->has($key)) {
                $value = $request->input($key);
                if (is_string($value)) {
                    $value = array_values(array_filter(array_map('trim', explode(',', $value))));
                }
                AppSetting::set($key, is_array($value) ? $value : [], 'json', 'settings');
            }
        }

        return back()->with('success', 'App settings updated successfully.');
    }
}
