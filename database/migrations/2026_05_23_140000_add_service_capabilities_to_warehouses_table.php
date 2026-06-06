<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'accepts_corporate')) {
                $table->boolean('accepts_corporate')->default(true)->after('status');
            }
            if (!Schema::hasColumn('warehouses', 'accepts_donation')) {
                $table->boolean('accepts_donation')->default(true)->after('accepts_corporate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'accepts_donation')) {
                $table->dropColumn('accepts_donation');
            }
            if (Schema::hasColumn('warehouses', 'accepts_corporate')) {
                $table->dropColumn('accepts_corporate');
            }
        });
    }
};
