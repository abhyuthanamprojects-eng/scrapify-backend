<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Warehouse;

// Mock the controller to test the protected method
$controller = new class extends \App\Http\Controllers\Api\WarehouseController {
    public function testGetWarehouse($user) {
        return $this->getWarehouse($user);
    }
};

echo "--- Testing Improved Warehouse Detection ---\n";

// Test 1: Admin User (ID 1)
$admin = User::find(1);
$w1 = $controller->testGetWarehouse($admin);
echo "Admin (ID 1) Assigned to: " . ($w1 ? $w1->name : 'NONE') . "\n";

// Test 2: User with Warehouse ID set explicitly
$user2 = User::find(2);
$user2->update(['warehouse_id' => 1]);
$w2 = $controller->testGetWarehouse($user2);
echo "User 2 with warehouse_id=1 Assigned to: " . ($w2 ? $w2->name : 'NONE') . "\n";

// Test 3: User as explicit manager
$user2->update(['warehouse_id' => null]);
$warehouse = Warehouse::find(2);
$warehouse->update(['manager_id' => 2]);
$w3 = $controller->testGetWarehouse($user2);
echo "User 2 as Manager of Warehouse 2 Assigned to: " . ($w3 ? $w3->name : 'NONE') . "\n";

// Cleanup
$user2->update(['warehouse_id' => null]);
$warehouse->update(['manager_id' => 1]); // Restore to original state
