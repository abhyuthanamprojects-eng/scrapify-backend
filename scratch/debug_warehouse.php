<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = User::find(1);
if (!$user) {
    echo "User 1 not found\n";
    exit;
}

echo "User: " . $user->name . " (Roles: " . implode(', ', $user->getRoleNames()->toArray()) . ")\n";

$warehouseIdExplicit = $user->warehouse_id;
echo "Explicit warehouse_id: " . ($warehouseIdExplicit ?: 'NULL') . "\n";

$warehouseByManager = Warehouse::where('manager_id', $user->id)->first();
echo "Warehouse by manager_id: " . ($warehouseByManager ? $warehouseByManager->name . ' (ID: ' . $warehouseByManager->id . ')' : 'NULL') . "\n";

$warehouseByChannelPartner = null;
if ($user->channel_partner_id) {
    $warehouseByChannelPartner = Warehouse::where('channel_partner_id', $user->channel_partner_id)->first();
}
echo "Warehouse by channel_partner_id: " . ($warehouseByChannelPartner ? $warehouseByChannelPartner->name : 'NULL') . "\n";

$firstWarehouse = Warehouse::first();
echo "First warehouse (fallback for admin): " . ($firstWarehouse ? $firstWarehouse->name : 'NULL') . "\n";
