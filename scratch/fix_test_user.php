<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ChannelPartner;

$phone = '9876543210';
$user = User::where('phone', $phone)->first();

if (!$user) {
    $user = User::create([
        'phone' => $phone,
        'name' => 'Test Partner',
        'email' => 'partner@test.com',
        'password' => bcrypt($phone),
        'status' => 'active',
    ]);
}

if (!$user->hasRole('channel_partner')) {
    $user->assignRole('channel_partner');
}

if (!$user->channel_partner_id) {
    $partner = ChannelPartner::updateOrCreate(
        ['phone' => $phone],
        [
            'full_name' => $user->name,
            'email' => $user->email,
            'business_name' => 'Partner ' . substr($phone, -4),
            'aadhaar_number' => 'TEST' . substr($phone, -8),
            'pan_number' => 'ABCDE' . substr($phone, -4) . 'A',
            'address' => 'Test Address',
            'city' => 'Test City',
            'state' => 'Test State',
            'pincode' => '400001',
            'opening_location_name' => 'Main Center',
            'registration_status' => 'approved',
            'login_enabled' => true,
            'user_id' => $user->id,
        ]
    );
    $user->update(['channel_partner_id' => $partner->id]);
    echo "Linked User {$user->id} to Partner {$partner->id}\n";
} else {
    echo "User already linked to Partner {$user->channel_partner_id}\n";
}
