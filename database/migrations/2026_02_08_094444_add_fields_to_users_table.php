<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->after('email')->unique()->nullable(); // Phone based auth
            $table->string('otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->decimal('wallet_balance', 10, 2)->default(0);
            $table->boolean('status')->default(true); // Active/Banned
            $table->string('profile_photo_path', 2048)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'otp', 'otp_expires_at', 'wallet_balance', 'status', 'profile_photo_path']);
        });
    }
};
