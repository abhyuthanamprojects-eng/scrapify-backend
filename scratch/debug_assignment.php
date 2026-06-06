<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

echo "--- Recent Personal Access Tokens ---\n";
$tokens = DB::table('personal_access_tokens')->latest()->limit(5)->get();
foreach ($tokens as $token) {
    $user = User::find($token->tokenable_id);
    echo "Token ID: " . $token->id . 
         " | User ID: " . $token->tokenable_id . 
         " | User Name: " . ($user ? $user->name : 'N/A') . 
         " | Roles: " . ($user ? $user->getRoleNames()->implode(',') : 'N/A') . "\n";
}

echo "\n--- Users without Warehouse Assignment but Warehouse/Admin Role ---\n";
$users = User::all();
foreach ($users as $user) {
    if ($user->hasRole(['admin', 'warehouse'])) {
        $warehouse = app(\App\Http\Controllers\Api\WarehouseController::class)->dashboard(new \Illuminate\Http\Request()); // This won't work easily due to Auth::user()
        
        // Let's just manually check the logic
        $assigned = false;
        if ($user->warehouse_id) $assigned = true;
        if (Warehouse::where('manager_id', $user->id)->exists()) $assigned = true;
        if ($user->hasRole('admin')) $assigned = true; // Based on fallback in controller
        
        if (!$assigned) {
            echo "ID: " . $user->id . " | Name: " . $user->name . " | Roles: " . $user->getRoleNames()->implode(',') . " | ASSIGNED: NO\n";
        } else {
             echo "ID: " . $user->id . " | Name: " . $user->name . " | Roles: " . $user->getRoleNames()->implode(',') . " | ASSIGNED: YES\n";
        }
    }
}
