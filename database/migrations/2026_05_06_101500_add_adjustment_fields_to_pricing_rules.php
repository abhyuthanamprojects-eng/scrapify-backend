<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('pricing_rules', 'adjustment_type')) {
                $table->enum('adjustment_type', ['fixed', 'percentage'])
                    ->default('fixed')
                    ->after('base_price');
            }

            if (!Schema::hasColumn('pricing_rules', 'adjustment_value')) {
                $table->decimal('adjustment_value', 10, 2)
                    ->nullable()
                    ->after('adjustment_type')
                    ->comment('For percentage type store +/- percent, for fixed store absolute delta');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_rules', 'adjustment_value')) {
                $table->dropColumn('adjustment_value');
            }
            if (Schema::hasColumn('pricing_rules', 'adjustment_type')) {
                $table->dropColumn('adjustment_type');
            }
        });
    }
};
