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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partner_id')->constrained('users')->onDelete('cascade'); // Channel Partner
            $table->foreignId('pickup_request_id')->constrained('pickup_requests')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2); // Total pickup value
            $table->decimal('commission_rate', 5, 2)->default(10.00); // Commission % (e.g., 10%)
            $table->decimal('commission_amount', 10, 2); // Calculated commission
            $table->decimal('net_amount', 10, 2); // Amount to be paid to partner
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null'); // Link to payment record
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
