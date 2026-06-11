<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('pickup_requests', 'payment_receipt_image')) {
                $table->string('payment_receipt_image')->nullable()->after('payment_reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            if (Schema::hasColumn('pickup_requests', 'payment_receipt_image')) {
                $table->dropColumn('payment_receipt_image');
            }
        });
    }
};
