<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_rules', 'carbon_per_unit')) {
                $table->decimal('carbon_per_unit', 10, 3)
                    ->nullable()
                    ->after('base_price')
                    ->comment('Estimated CO2 saved in kg for one pricing unit');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_rules', 'carbon_per_unit')) {
                $table->dropColumn('carbon_per_unit');
            }
        });
    }
};
