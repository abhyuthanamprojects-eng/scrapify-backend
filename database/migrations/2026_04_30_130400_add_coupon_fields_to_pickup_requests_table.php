<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->foreignId('referral_coupon_id')->nullable()->after('estimated_amount')->constrained('referral_coupons')->nullOnDelete();
            $table->string('coupon_code', 12)->nullable()->after('referral_coupon_id');
            $table->decimal('coupon_discount_value', 10, 2)->nullable()->after('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropForeign(['referral_coupon_id']);
            $table->dropColumn(['referral_coupon_id', 'coupon_code', 'coupon_discount_value']);
        });
    }
};
