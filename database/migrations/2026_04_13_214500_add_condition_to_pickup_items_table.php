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
        Schema::table('pickup_items', function (Blueprint $table) {
            if (!Schema::hasColumn('pickup_items', 'condition')) {
                $table->string('condition')->nullable()->after('weight');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_items', function (Blueprint $table) {
            $table->dropColumn('condition');
        });
    }
};
