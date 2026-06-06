<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Warehouse;

echo "--- All Users ---\n";
$users = User::with('roles')->get();
foreach ($users as $user) {
    echo "ID: " . $user->id . 
         " | Name: " . $user->name . 
         " | Phone: " . $user->phone . 
         " | Warehouse ID: " . ($user->warehouse_id ?? 'NULL') . 
         " | Roles: " . $user->roles->pluck('name')->implode(', ') . "\n";
}

echo "\n--- Warehouses ---\n";
$warehouses = Warehouse::all();
foreach ($warehouses as $w) {
    echo "ID: " . $w->id . " | Name: " . $w->name . " | Manager ID: " . ($w->manager_id ?? 'NULL') . "\n";
}
