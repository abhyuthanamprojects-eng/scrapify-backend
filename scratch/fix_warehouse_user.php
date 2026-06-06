<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Warehouse;

/**
 * UTILITY SCRIPT: Fix Warehouse User Assignment
 * 
 * Usage: Update the variables below and run `php scratch/fix_warehouse_user.php`
 */

$phone = '9876543210'; // CHANGE THIS to the phone number being tested
$warehouseId = 1;    // CHANGE THIS to the desired warehouse ID

echo "--- Fixing Warehouse Assignment for User: $phone ---\n";

$user = User::where('phone', $phone)->first();

if (!$user) {
    die("Error: User with phone $phone not found.\n");
}

$warehouse = Warehouse::find($warehouseId);
if (!$warehouse) {
    die("Error: Warehouse with ID $warehouseId not found.\n");
}

// Ensure user has the warehouse role
if (!$user->hasRole('warehouse')) {
    $user->assignRole('warehouse');
    echo "Assigned 'warehouse' role to user.\n";
}

// Assign to warehouse explicitly
$user->update(['warehouse_id' => $warehouseId]);
echo "Updated user 'warehouse_id' to $warehouseId.\n";

// Ensure the warehouse recognizes them as a manager (optional fallback)
if ($warehouse->manager_id !== $user->id) {
    $warehouse->update(['manager_id' => $user->id]);
    echo "Updated warehouse 'manager_id' to {$user->id}.\n";
}

echo "SUCCESS: User $phone is now assigned to warehouse '{$warehouse->name}'.\n";
