<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_types', function (Blueprint $table) {
            if (!Schema::hasColumn('category_types', 'show_in_corporate_booking')) {
                $table->boolean('show_in_corporate_booking')->default(false)->after('status');
            }
        });

        $enabledSlugs = [
            Str::slug('E-Waste, Electrical & Digital Devices'),
            Str::slug('Metals, Power & Energy Hub'),
            Str::slug('Old Furniture'),
            Str::slug('E-Waste'),
            Str::slug('Metal Scrap'),
            Str::slug('Furniture Scrap'),
        ];

        DB::table('category_types')->update(['show_in_corporate_booking' => false]);
        DB::table('category_types')
            ->whereIn('slug', $enabledSlugs)
            ->update(['show_in_corporate_booking' => true]);
    }

    public function down(): void
    {
        Schema::table('category_types', function (Blueprint $table) {
            if (Schema::hasColumn('category_types', 'show_in_corporate_booking')) {
                $table->dropColumn('show_in_corporate_booking');
            }
        });
    }
};
