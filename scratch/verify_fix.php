<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Warehouse;
use App\Http\Controllers\Api\WarehouseController;

class TestWarehouseController extends WarehouseController {
    public function testGetWarehouse($user) {
        return $this->getWarehouse($user);
    }
}

$controller = new TestWarehouseController();

// 1. Test Admin with no assignment
$admin = User::role('admin')->first();
if ($admin) {
    // Force clear warehouse_id and ensure not a manager for testing fallback
    // We don't actually save to DB, just modify in memory
    $admin->warehouse_id = null;
    
    // We need to ensure they are NOT a manager of any warehouse for this test
    // To do this properly without changing DB, we'd need to mock the relationship 
    // but here we can just check if they ARE a manager and if so, understand they won't fallback.
    
    $isManager = Warehouse::where('manager_id', $admin->id)->exists();
    echo "Is Admin ID {$admin->id} a manager? " . ($isManager ? 'Yes' : 'No') . PHP_EOL;
    
    $warehouse = $controller->testGetWarehouse($admin);
    echo "Admin Fallback Result: " . ($warehouse ? $warehouse->name : 'NULL') . PHP_EOL;
}

// 2. Test Non-Admin with no assignment
$user = User::whereDoesntHave('roles', function($q) { $q->where('name', 'admin'); })->first();
if ($user) {
    $user->warehouse_id = null;
    // Ensure they aren't a manager
    if (!Warehouse::where('manager_id', $user->id)->exists()) {
        $warehouse = $controller->testGetWarehouse($user);
        echo "Non-Admin Result (should be NULL): " . ($warehouse ? $warehouse->name : 'NULL') . PHP_EOL;
    }
}
