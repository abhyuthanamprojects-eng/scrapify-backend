<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referred_user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('referral_code', 6);
            $table->enum('status', ['pending', 'successful', 'expired', 'rejected'])->default('pending');
            $table->enum('reward_status', ['pending', 'issued', 'used', 'expired'])->default('pending');
            $table->unsignedBigInteger('reward_coupon_id')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
