<?php

namespace Tests\Unit;

use App\Models\AppSetting;
use App\Models\User;
use App\Models\PickupRequest;
use App\Notifications\PickupStatusNotification;
use App\Notifications\Channels\Msg91SmsChannel;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SmsConfigResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Override DB configuration to SQLite in-memory for this test
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        // Create the required tables for testing
        Schema::create('app_settings', function ($table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('type')->default('string');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('phone')->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamps();
        });

        Schema::create('pickup_requests', function ($table) {
            $table->id();
            $table->string('pickup_code')->nullable();
            $table->string('status')->nullable();
            $table->string('request_type')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('reschedule_reason')->nullable();
            $table->timestamps();
        });
    }

    public function test_pickup_status_notification_resolves_auth_key_from_app_setting()
    {
        // 1. Arrange: No AppSetting, config has auth key
        config(['services.msg91.auth_key' => 'config_auth_key']);
        
        $user = new User(['phone' => '1234567890']);
        $pickup = new PickupRequest(['pickup_code' => 'P123', 'status' => 'pending']);
        $notification = new PickupStatusNotification($pickup, 'pending');

        // Verify fallback to config
        $channels = $notification->via($user);
        $this->assertContains(Msg91SmsChannel::class, $channels);

        // Clear config auth key, verify it is empty and not loaded
        config(['services.msg91.auth_key' => null]);
        $channels = $notification->via($user);
        $this->assertNotContains(Msg91SmsChannel::class, $channels);

        // 2. Set AppSetting auth key
        AppSetting::set('msg91_auth_key', 'db_auth_key');

        // Verify it resolves from AppSetting even if config is null
        $channels = $notification->via($user);
        $this->assertContains(Msg91SmsChannel::class, $channels);
    }

    public function test_pickup_status_notification_resolves_fallback_template_id()
    {
        // Arrange
        config(['services.msg91.sms_template_id' => 'config_template_id']);
        
        $user = new User(['phone' => '1234567890']);
        $pickup = new PickupRequest(['pickup_code' => 'P123', 'status' => 'pending']);
        $notification = new PickupStatusNotification($pickup, 'pending');

        // toMsg91 should fallback to config template ID if specific db settings are missing
        AppSetting::set('msg91_pickup_booked_template_id', null);
        AppSetting::set('msg91_sms_template_id', null);

        $data = $notification->toMsg91($user);
        $this->assertEquals('config_template_id', $data['template_id']);

        // Set default template ID in DB
        AppSetting::set('msg91_sms_template_id', 'db_default_template_id');
        $data = $notification->toMsg91($user);
        $this->assertEquals('db_default_template_id', $data['template_id']);
    }
}
