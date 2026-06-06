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
        // Payments (Payouts to customers or Pickup Boy earnings)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('pickup_request_id')->nullable()->constrained()->onDelete('set null'); // Linked to specific pickup
            $table->decimal('amount', 10, 2);
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'approved'])->default('pending');
            $table->enum('type', ['bank_transfer', 'upi', 'cash', 'wallet'])->default('cash');
            $table->string('proof_image_path')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // KYC Documents
        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', ['aadhaar_front', 'aadhaar_back', 'pan_card', 'driving_license']);
            $table->string('document_number')->nullable();
            $table->string('image_path');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        // Warehouses
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Inventory Logs
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->decimal('weight', 10, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->enum('type', ['in', 'out', 'adjustment']); // In from pickup, Out to recycler
            $table->string('reference_id')->nullable(); // e.g., Batch ID
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('kyc_documents');
        Schema::dropIfExists('payments');
    }
};
