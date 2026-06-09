<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClearBookingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key constraints so we can truncate safely
        Schema::disableForeignKeyConstraints();

        // Array of tables to clear
        $tables = [
            'pickup_items',
            'pickup_images',
            'pickup_request_attributes',
            'pickup_assignment_histories',
            'pickup_status_logs',
            'pickup_price_logs',
            'assignments',
            'corporate_booking_estimates',
            'payments',
            'payment_details',
            'inventory_logs',
            'settlements',
            'withdrawals',
            'pickup_requests',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
                $this->command->info("Truncated {$table}");
            }
        }

        // Also delete any notifications related to bookings
        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->whereIn('type', [
                    'request_created', 
                    'new_request', 
                    'pickup_boy_assigned', 
                    'booking_status_updated',
                    'estimate_approved',
                    'estimate_rejected',
                    'payment_pending'
                ])
                ->delete();
            $this->command->info("Deleted booking-related notifications");
        }

        // Re-enable foreign key constraints
        Schema::enableForeignKeyConstraints();

        $this->command->info('All booking and related data has been cleared!');
    }
}
