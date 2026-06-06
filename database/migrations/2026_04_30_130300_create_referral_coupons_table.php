<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('referral_id')->nullable()->constrained('referrals')->nullOnDelete();
            $table->string('coupon_code', 12)->unique();
            $table->enum('coupon_type', ['fixed', 'percentage', 'extra_value'])->default('extra_value');
            $table->decimal('coupon_value', 10, 2)->default(0);
            $table->decimal('min_booking_value', 10, 2)->nullable();
            $table->decimal('max_discount_value', 10, 2)->nullable();
            $table->date('expiry_date');
            $table->enum('status', ['active', 'used', 'expired', 'cancelled'])->default('active');
            $table->foreignId('used_booking_id')->nullable()->constrained('pickup_requests')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_role')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::table('referrals', function (Blueprint $table) {
            $table->foreign('reward_coupon_id')->references('id')->on('referral_coupons')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropForeign(['reward_coupon_id']);
        });
        Schema::dropIfExists('referral_coupons');
    }
};
