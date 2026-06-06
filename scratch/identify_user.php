<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tokenId = 18;
$token = DB::table('personal_access_tokens')->find($tokenId);

if ($token) {
    echo "Token ID: " . $token->id . PHP_EOL;
    echo "User ID: " . $token->tokenable_id . PHP_EOL;
    $user = App\Models\User::find($token->tokenable_id);
    if ($user) {
        echo "User Name: " . $user->name . PHP_EOL;
        echo "User Email: " . $user->email . PHP_EOL;
        echo "User Phone: " . $user->phone . PHP_EOL;
        echo "Warehouse ID: " . ($user->warehouse_id ?? 'None') . PHP_EOL;
        echo "Roles: " . $user->getRoleNames()->implode(', ') . PHP_EOL;
    } else {
        echo "User not found." . PHP_EOL;
    }
} else {
    echo "Token 18 not found in personal_access_tokens." . PHP_EOL;
    // Let's look for the most recent tokens
    $tokens = DB::table('personal_access_tokens')->latest()->limit(5)->get();
    foreach ($tokens as $t) {
        echo "Found Token ID: " . $t->id . " for User ID: " . $t->tokenable_id . PHP_EOL;
    }
}
