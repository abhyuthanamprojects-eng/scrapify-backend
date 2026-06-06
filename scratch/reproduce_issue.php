<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Warehouse;

function getWarehouse($user) {
    if ($user->warehouse_id) {
        return Warehouse::find($user->warehouse_id);
    }
    $warehouse = Warehouse::where('manager_id', $user->id)->first();
    if (!$warehouse && $user->hasRole('admin')) {
        return Warehouse::first();
    }
    return $warehouse;
}

$user = User::find(1);
$warehouse = getWarehouse($user);
echo "User 1 (Admin) Warehouse: " . ($warehouse ? $warehouse->id : 'NULL') . "\n";

$user2 = User::find(2);
$warehouse2 = getWarehouse($user2);
echo "User 2 (Customer/Pickup Boy) Warehouse: " . ($warehouse2 ? $warehouse2->id : 'NULL') . "\n";
echo "User 2 Roles: " . $user2->getRoleNames()->implode(',') . "\n";
