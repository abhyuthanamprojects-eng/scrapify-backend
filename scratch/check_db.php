<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Warehouse;
use App\Models\User;

$user = User::find(1);
if ($user) {
    echo "User 1: " . $user->name . PHP_EOL;
    $warehouse = Warehouse::where('manager_id', $user->id)->first();
    echo "Warehouse Managed: " . ($warehouse ? $warehouse->name : 'None') . PHP_EOL;
    echo "User warehouse_id: " . ($user->warehouse_id ?? 'NULL') . PHP_EOL;
} else {
    echo "User 1 not found." . PHP_EOL;
}

$allWarehouses = Warehouse::all();
foreach ($allWarehouses as $w) {
    echo "Warehouse ID: {$w->id}, Name: {$w->name}, Manager ID: {$w->manager_id}" . PHP_EOL;
}
