<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use App\Models\User;
use App\Models\Warehouse;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting warehouse assignment fix...\n";

$warehouse = Warehouse::first();

if (!$warehouse) {
    echo "ERROR: No warehouses found in database. Create a warehouse first.\n";
    exit(1);
}

echo "Using warehouse: " . $warehouse->name . " (ID: " . $warehouse->id . ")\n";

$users = User::role('warehouse')->whereNull('warehouse_id')->get();

if ($users->isEmpty()) {
    echo "No unassigned warehouse users found.\n";
} else {
    foreach ($users as $user) {
        $user->update(['warehouse_id' => $warehouse->id]);
        echo "Assigned user: " . $user->name . " (" . $user->phone . ") to " . $warehouse->name . "\n";
    }
}

// Also check for the admin user specifically if they are a manager
$admin = User::role('admin')->first();
if ($admin && !$warehouse->manager_id) {
    $warehouse->update(['manager_id' => $admin->id]);
    echo "Set Admin (" . $admin->name . ") as manager for " . $warehouse->name . "\n";
}

echo "Fix complete.\n";
