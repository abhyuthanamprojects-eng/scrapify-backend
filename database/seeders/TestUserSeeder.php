<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'customer' => '9000000001',
            'pickup_boy' => '9000000002',
            'channel_partner' => '9000000003',
            'warehouse' => '9000000004',
            'payment_admin' => '9000000005'
        ];

        foreach ($roles as $roleName => $phone) {
            $email = ($roleName === 'pickup_boy' ? 'agent' : $roleName) . '@ewaste.com';
            
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => ucfirst(str_replace('_', ' ', $roleName)) . ' User',
                    'password' => Hash::make('password'),
                    'phone' => $phone,
                    'status' => true,
                ]
            );

            // Ensure the role exists before assigning
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            // If this is a warehouse user, link it to the first available warehouse
            if ($roleName === 'warehouse') {
                $firstWarehouse = \App\Models\Warehouse::first();
                if ($firstWarehouse) {
                    $user->update(['warehouse_id' => $firstWarehouse->id]);
                }
            }
        }
    }
}
