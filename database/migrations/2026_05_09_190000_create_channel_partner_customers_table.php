<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_partner_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_partner_id')->constrained('channel_partners')->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile', 20);
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('landmark')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['channel_partner_id', 'mobile'], 'cp_customer_mobile_unique');
            $table->index(['channel_partner_id', 'name']);
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('pickup_requests', 'partner_customer_id')) {
                $table->foreignId('partner_customer_id')
                    ->nullable()
                    ->after('customer_id')
                    ->constrained('channel_partner_customers')
                    ->nullOnDelete();
            }
        });

        Schema::table('pickup_images', function (Blueprint $table) {
            if (!Schema::hasColumn('pickup_images', 'pickup_item_id')) {
                $table->foreignId('pickup_item_id')
                    ->nullable()
                    ->after('pickup_request_id')
                    ->constrained('pickup_items')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('pickup_images', 'remarks')) {
                $table->text('remarks')->nullable()->after('type');
            }
        });

        Schema::table('settlements', function (Blueprint $table) {
            if (!Schema::hasColumn('settlements', 'payout_status')) {
                $table->string('payout_status')->default('pending')->after('status');
            }
            if (!Schema::hasColumn('settlements', 'payout_date')) {
                $table->date('payout_date')->nullable()->after('payment_id');
            }
            if (!Schema::hasColumn('settlements', 'payment_proof')) {
                $table->string('payment_proof')->nullable()->after('payout_date');
            }
        });

        DB::statement("ALTER TABLE pickup_requests MODIFY COLUMN status ENUM(
            'pending',
            'created',
            'assigned',
            'accepted',
            'on_the_way',
            'arrived',
            'reached_location',
            'verifying',
            'pickup_started',
            'picked_up',
            'pickup_completed',
            'completed',
            'delivered_to_warehouse',
            'cancelled',
            'rescheduled',
            'reschedule_requested'
        ) DEFAULT 'pending'");

        DB::statement("ALTER TABLE assignments MODIFY COLUMN status ENUM(
            'assigned',
            'accepted',
            'rejected',
            'on_the_way',
            'arrived',
            'reached_location',
            'verifying',
            'pickup_started',
            'picked_up',
            'pickup_completed',
            'completed',
            'delivered_to_warehouse',
            'cancelled',
            'rescheduled',
            'reschedule_requested',
            'reassigned'
        ) DEFAULT 'assigned'");
    }

    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $table->dropColumn(['payout_status', 'payout_date', 'payment_proof']);
        });

        Schema::table('pickup_images', function (Blueprint $table) {
            $table->dropForeign(['pickup_item_id']);
            $table->dropColumn(['pickup_item_id', 'remarks']);
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropForeign(['partner_customer_id']);
            $table->dropColumn('partner_customer_id');
        });

        Schema::dropIfExists('channel_partner_customers');
    }
};
