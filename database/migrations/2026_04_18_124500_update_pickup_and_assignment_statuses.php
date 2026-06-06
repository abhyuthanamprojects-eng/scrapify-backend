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
        // Update pickup_requests status ENUM
        DB::statement("ALTER TABLE pickup_requests MODIFY COLUMN status ENUM(
            'pending', 
            'assigned', 
            'accepted', 
            'on_the_way', 
            'arrived', 
            'verifying', 
            'picked_up', 
            'completed', 
            'cancelled', 
            'rescheduled'
        ) DEFAULT 'pending'");

        // Update assignments status ENUM
        DB::statement("ALTER TABLE assignments MODIFY COLUMN status ENUM(
            'assigned', 
            'accepted', 
            'rejected', 
            'on_the_way', 
            'arrived', 
            'verifying', 
            'completed', 
            'cancelled', 
            'rescheduled'
        ) DEFAULT 'assigned'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert pickup_requests status ENUM to original
        DB::statement("ALTER TABLE pickup_requests MODIFY COLUMN status ENUM(
            'pending', 
            'assigned', 
            'on_the_way', 
            'picked_up', 
            'completed', 
            'cancelled'
        ) DEFAULT 'pending'");

        // Revert assignments status ENUM to original
        DB::statement("ALTER TABLE assignments MODIFY COLUMN status ENUM(
            'assigned', 
            'accepted', 
            'rejected', 
            'completed'
        ) DEFAULT 'assigned'");
    }
};
