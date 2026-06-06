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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_available')) {
                $table->boolean('is_available')->default(false)->after('is_online');
            }
            if (!Schema::hasColumn('users', 'location_updated_at')) {
                $table->timestamp('location_updated_at')->nullable()->after('is_available');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_available', 'location_updated_at']);
        });
    }
};
