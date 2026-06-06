<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_items', function (Blueprint $table) {
            $table->string('product_name')->nullable()->after('category_id');
            $table->text('remarks')->nullable()->after('image_path');
            // Make category_id nullable so pickup boy can add ad-hoc items
            $table->foreignId('category_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pickup_items', function (Blueprint $table) {
            $table->dropColumn(['product_name', 'remarks']);
        });
    }
};
