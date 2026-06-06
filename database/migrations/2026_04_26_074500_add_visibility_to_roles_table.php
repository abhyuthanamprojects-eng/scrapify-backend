<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('visible')->default(true)->after('guard_name');
            $table->integer('sort_order')->default(0)->after('visible');
        });

        // Set default visibility and order
        DB::table('roles')->where('name', 'customer')->update(['sort_order' => 1, 'visible' => true]);
        DB::table('roles')->where('name', 'channel_partner')->update(['sort_order' => 2, 'visible' => true]);
        DB::table('roles')->where('name', 'pickup_boy')->update(['sort_order' => 3, 'visible' => true]);
        DB::table('roles')->where('name', 'warehouse')->update(['sort_order' => 4, 'visible' => true]);
        DB::table('roles')->where('name', 'admin')->update(['sort_order' => 99, 'visible' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['visible', 'sort_order']);
        });
    }
};
