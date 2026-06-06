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
        // Add type column to categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('type', ['electronics', 'metal', 'plastic'])->default('electronics')->after('slug');
        });

        // Add pricing_type and min_quantity columns to pricing_rules table
        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->enum('pricing_type', ['per_kg', 'per_piece', 'per_capacity'])->default('per_piece')->after('category_id');
            $table->integer('min_quantity')->default(1)->after('base_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('pricing_rules', function (Blueprint $table) {
            $table->dropColumn(['pricing_type', 'min_quantity']);
        });
    }
};
