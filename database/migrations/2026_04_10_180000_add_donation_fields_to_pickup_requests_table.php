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
            if (!Schema::hasColumn('pickup_requests', 'request_type')) {
                $table->enum('request_type', ['scrap', 'donation'])->default('scrap')->after('id');
            }
            if (!Schema::hasColumn('pickup_requests', 'donation_category')) {
                $table->string('donation_category')->nullable()->after('request_type');
            }
            // Allow null for estimated_amount
            $table->decimal('estimated_amount', 10, 2)->nullable()->change();
        });

        Schema::table('pickup_items', function (Blueprint $table) {
            // Allow null for pricing fields
            $table->decimal('price_per_unit', 10, 2)->nullable()->change();
            $table->decimal('total_price', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropColumn(['request_type', 'donation_category']);
            $table->decimal('estimated_amount', 10, 2)->default(0)->change();
        });

        Schema::table('pickup_items', function (Blueprint $table) {
            $table->decimal('price_per_unit', 10, 2)->change();
            $table->decimal('total_price', 10, 2)->change();
        });
    }
};
