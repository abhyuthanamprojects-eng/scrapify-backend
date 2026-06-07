<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update pickup_requests table with new fields
        Schema::table('pickup_requests', function (Blueprint $table) {
            // Add new status field (will migrate old data)
            if (!Schema::hasColumn('pickup_requests', 'status_new')) {
                $table->string('status_new')->default('pending_warehouse')->after('status');
            }

            // Add warehouse and assignment tracking
            if (!Schema::hasColumn('pickup_requests', 'warehouse_assigned_at')) {
                $table->timestamp('warehouse_assigned_at')->nullable()->after('warehouse_id');
            }

            // Add pickup timeline
            if (!Schema::hasColumn('pickup_requests', 'pickup_started_at')) {
                $table->timestamp('pickup_started_at')->nullable()->after('warehouse_assigned_at');
            }
            if (!Schema::hasColumn('pickup_requests', 'pickup_completed_at')) {
                $table->timestamp('pickup_completed_at')->nullable()->after('pickup_started_at');
            }

            // Add warehouse receive tracking
            if (!Schema::hasColumn('pickup_requests', 'warehouse_received_at')) {
                $table->timestamp('warehouse_received_at')->nullable()->after('pickup_completed_at');
            }
            if (!Schema::hasColumn('pickup_requests', 'warehouse_received_by')) {
                $table->foreignId('warehouse_received_by')->nullable()->constrained('users')->onDelete('set null');
            }

            // Add payment tracking
            if (!Schema::hasColumn('pickup_requests', 'payment_pending_at')) {
                $table->timestamp('payment_pending_at')->nullable()->after('warehouse_received_by');
            }
            if (!Schema::hasColumn('pickup_requests', 'payment_completed_at')) {
                $table->timestamp('payment_completed_at')->nullable()->after('payment_pending_at');
            }
            if (!Schema::hasColumn('pickup_requests', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'processing', 'completed', 'failed'])->nullable();
            }
            if (!Schema::hasColumn('pickup_requests', 'payment_method')) {
                $table->string('payment_method')->nullable();
            }
            if (!Schema::hasColumn('pickup_requests', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->unique();
            }
            if (!Schema::hasColumn('pickup_requests', 'receiver_name')) {
                $table->string('receiver_name')->nullable();
            }

            // Add completion tracking
            if (!Schema::hasColumn('pickup_requests', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('payment_completed_at');
            }

            // Add who assigned it
            if (!Schema::hasColumn('pickup_requests', 'assigned_by')) {
                $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            }

            // Add next allowed actions (for API response)
            if (!Schema::hasColumn('pickup_requests', 'next_allowed_actions')) {
                $table->json('next_allowed_actions')->nullable();
            }
        });

        // Create request_status_logs table for history tracking
        Schema::create('request_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('pickup_requests')->onDelete('cascade');
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->foreignId('changed_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('changed_by_role'); // admin, warehouse, pickup_boy, customer, system
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Any additional context
            $table->timestamp('created_at');
        });

        // Create corporate_booking_estimates table
        Schema::create('corporate_booking_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('pickup_requests')->onDelete('cascade');
            $table->decimal('estimated_amount', 12, 2);
            $table->decimal('estimated_weight', 10, 2)->nullable();
            $table->integer('estimated_items_count')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'shared', 'approved', 'rejected'])->default('pending');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['request_id', 'status']);
            $table->index(['created_by']);
        });

        // Update assignments table with timeline
        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'assigned_at')) {
                $table->timestamp('assigned_at')->default(now())->after('status');
            }
            if (!Schema::hasColumn('assignments', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('assigned_at');
            }
            if (!Schema::hasColumn('assignments', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('accepted_at');
            }
            if (!Schema::hasColumn('assignments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('started_at');
            }

            // Update status enum to include new statuses
            // Note: This might require data migration depending on current values
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_booking_estimates');
        Schema::dropIfExists('request_status_logs');

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn([
                'assigned_at',
                'accepted_at',
                'started_at',
                'completed_at',
            ]);
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropColumn([
                'status_new',
                'warehouse_assigned_at',
                'pickup_started_at',
                'pickup_completed_at',
                'warehouse_received_at',
                'warehouse_received_by',
                'payment_pending_at',
                'payment_completed_at',
                'payment_status',
                'payment_method',
                'payment_reference',
                'completed_at',
                'assigned_by',
                'next_allowed_actions',
            ]);
        });
    }
};
