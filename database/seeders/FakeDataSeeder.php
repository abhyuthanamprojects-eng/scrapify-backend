<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FakeDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get Roles
        $adminRole = Role::where('name', 'admin')->first();
        $customerRole = Role::where('name', 'customer')->first();
        $warehouseRole = Role::where('name', 'warehouse')->first();
        $partnerRole = Role::where('name', 'channel_partner')->first();
        $pickupBoyRole = Role::where('name', 'pickup_boy')->first();

        // 2. Ensure at least one city exists for locations
        $city = City::first();
        $cityId = $city ? $city->id : 1;

        // 3. Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@ewaste.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '1111111111', // Changed from 9999999999 so customer can use it
                'status' => true,
                'city_id' => $cityId,
            ]
        );
        if ($adminRole && !$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        // 4. Dummy Customer
        $customer = User::firstOrCreate(
            ['phone' => '9999999999'],
            [
                'name' => 'Dummy Customer',
                'email' => 'customer@ewaste.com',
                'password' => Hash::make('999999'),
                'status' => true,
                'city_id' => $cityId,
            ]
        );
        if ($customerRole && !$customer->hasRole('customer')) {
            $customer->assignRole($customerRole);
        }

        // 5. Dummy Warehouse Manager
        $warehouseUser = User::firstOrCreate(
            ['phone' => '8888888888'],
            [
                'name' => 'Dummy Warehouse Manager',
                'email' => 'warehouse@ewaste.com',
                'password' => Hash::make('888888'),
                'status' => true,
                'city_id' => $cityId,
            ]
        );
        if ($warehouseRole && !$warehouseUser->hasRole('warehouse')) {
            $warehouseUser->assignRole($warehouseRole);
        }

        // 6. Dummy Channel Partner
        $partnerUser = User::firstOrCreate(
            ['phone' => '7777777777'],
            [
                'name' => 'Dummy Partner',
                'email' => 'partner@ewaste.com',
                'password' => Hash::make('777777'),
                'status' => true,
                'city_id' => $cityId,
            ]
        );
        if ($partnerRole && !$partnerUser->hasRole('channel_partner')) {
            $partnerUser->assignRole($partnerRole);
        }

        // Create Channel Partner Record
        $channelPartnerRecord = \App\Models\ChannelPartner::firstOrCreate(
            ['user_id' => $partnerUser->id],
            [
                'full_name' => 'Dummy Partner',
                'phone' => '7777777777',
                'email' => 'partner@ewaste.com',
                'business_name' => 'Dummy Partner Business',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'address' => '456 Dummy Office Complex, BKC',
                'pincode' => '400051',
                'aadhaar_number' => '123456789012',
                'pan_number' => 'ABCDE1234F',
                'opening_location_name' => 'Mumbai Main Center',
                'registration_status' => 'approved',
                'login_enabled' => true,
                'warehouse_limit' => 5,
            ]
        );

        $partnerUser->update(['channel_partner_id' => $channelPartnerRecord->id]);

        // 7. Dummy Pickup Boy
        $pickupBoy = User::firstOrCreate(
            ['phone' => '6666666666'],
            [
                'name' => 'Dummy Pickup Boy',
                'email' => 'pickupboy@ewaste.com',
                'password' => Hash::make('666666'),
                'status' => true,
                'city_id' => $cityId,
                'channel_partner_id' => $channelPartnerRecord->id,
            ]
        );
        if ($pickupBoyRole && !$pickupBoy->hasRole('pickup_boy')) {
            $pickupBoy->assignRole($pickupBoyRole);
        }

        // 8. Create a physical Warehouse and link users
        $warehouseRecord = Warehouse::firstOrCreate(
            ['manager_id' => $warehouseUser->id],
            [
                'name' => 'Main Warehouse',
                'city_id' => $cityId,
                'address' => '123 Industrial Area',
                'latitude' => 19.0760,
                'longitude' => 72.8777,
                'status' => true,
                'code' => 'MWH-01',
                'channel_partner_id' => $channelPartnerRecord->id, // Assign to partner profile
            ]
        );

        // Assign warehouse to pickup boy and warehouse user
        $pickupBoy->update(['warehouse_id' => $warehouseRecord->id]);
        $warehouseUser->update(['warehouse_id' => $warehouseRecord->id, 'channel_partner_id' => $channelPartnerRecord->id]);

        // 9. Delhi Warehouse
        $delhiCity = City::where('name', 'New Delhi')->first();
        if ($delhiCity) {
            $delhiWarehouseUser = User::firstOrCreate(
                ['phone' => '8888888889'],
                [
                    'name' => 'Delhi Warehouse Manager',
                    'email' => 'delhi_warehouse@ewaste.com',
                    'password' => Hash::make('888888'),
                    'status' => true,
                    'city_id' => $delhiCity->id,
                ]
            );
            if ($warehouseRole && !$delhiWarehouseUser->hasRole('warehouse')) {
                $delhiWarehouseUser->assignRole($warehouseRole);
            }

            $delhiWarehouseRecord = Warehouse::firstOrCreate(
                ['manager_id' => $delhiWarehouseUser->id],
                [
                    'name' => 'Delhi Warehouse',
                    'city_id' => $delhiCity->id,
                    'address' => '456 Delhi Industrial Area',
                    'latitude' => 28.6139,
                    'longitude' => 77.2090,
                    'status' => true,
                    'code' => 'DLWH-01',
                ]
            );

            $delhiWarehouseUser->update(['warehouse_id' => $delhiWarehouseRecord->id]);
        }
    }
}
