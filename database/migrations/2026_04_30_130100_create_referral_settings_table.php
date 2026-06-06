<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('managed_by_role')->nullable();
            $table->string('campaign_name');
            $table->boolean('is_active')->default(true);
            $table->enum('reward_type', ['fixed', 'percentage', 'extra_value'])->default('extra_value');
            $table->decimal('reward_value', 10, 2)->default(0);
            $table->unsignedInteger('coupon_expiry_days')->default(30);
            $table->decimal('min_booking_value', 10, 2)->nullable();
            $table->decimal('max_reward_value', 10, 2)->nullable();
            $table->unsignedInteger('max_referrals_per_user')->default(30);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_settings');
    }
};
