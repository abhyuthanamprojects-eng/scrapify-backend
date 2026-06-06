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
        Schema::create('channel_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Linked once approved
            
            // Business Details
            $table->string('full_name');
            $table->string('phone')->unique();
            $table->string('email')->unique();
            $table->string('aadhaar_number')->unique();
            $table->string('pan_number')->unique();
            $table->string('gst_number')->nullable();
            $table->string('business_name');
            
            // Location Details
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('pincode');
            $table->string('opening_location_name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Status & Verification
            $table->enum('registration_status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending');
            $table->text('admin_remark')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Onboarding Fees
            $table->boolean('onboarding_fee_required')->default(true);
            $table->decimal('onboarding_fee_amount', 10, 2)->default(0);
            $table->enum('fee_payment_status', ['pending', 'paid', 'waived'])->default('pending');
            $table->dateTime('fee_paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('payment_remark')->nullable();
            
            // Approval Metadata
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            
            $table->boolean('login_enabled')->default(false);
            $table->timestamps();
        });

        // Add channel_partner_id to existing tables for scoping
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('channel_partner_id')->nullable()->after('id')->constrained('channel_partners')->onDelete('set null');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->foreignId('channel_partner_id')->nullable()->after('id')->constrained('channel_partners')->onDelete('set null');
        });

        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->foreignId('channel_partner_id')->nullable()->after('id')->constrained('channel_partners')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropForeign(['channel_partner_id']);
            $table->dropColumn('channel_partner_id');
        });

        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropForeign(['channel_partner_id']);
            $table->dropColumn('channel_partner_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['channel_partner_id']);
            $table->dropColumn('channel_partner_id');
        });

        Schema::dropIfExists('channel_partners');
    }
};
