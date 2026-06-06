<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE pickup_requests MODIFY COLUMN request_type ENUM('scrap', 'donation', 'corporate') DEFAULT 'scrap'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Removing an enum value is generally tricky without creating a new column or recreating the table.
        // For safety, we'll just allow it to remain or revert it back to original (but existing 'corporate' values might cause errors).
        // DB::statement("ALTER TABLE pickup_requests MODIFY COLUMN request_type ENUM('scrap', 'donation') DEFAULT 'scrap'");
    }
};
