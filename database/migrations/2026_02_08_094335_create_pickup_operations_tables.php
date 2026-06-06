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
        // Pickup Requests
        Schema::create('pickup_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->text('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'assigned', 'on_the_way', 'picked_up', 'completed', 'cancelled'])->default('pending');
            $table->decimal('estimated_amount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2)->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable(); // For extra details
            $table->timestamps();
            $table->softDeletes();
        });

        // Pickup Items (Actual items collected)
        Schema::create('pickup_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->decimal('weight', 8, 2)->nullable(); // e.g., 2.5 kg
            $table->integer('quantity')->nullable(); // e.g., 2 pcs
            $table->decimal('price_per_unit', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('image_path')->nullable();
            $table->timestamps();
        });

        // Pickup Images (Proof of pickup, item images, etc)
        Schema::create('pickup_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->string('type')->default('item'); // item, proof, signature
            $table->timestamps();
        });

        // Assignments (Linking Pickup Boy to Request)
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('pickup_boy_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['assigned', 'accepted', 'rejected', 'completed'])->default('assigned');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('pickup_images');
        Schema::dropIfExists('pickup_items');
        Schema::dropIfExists('pickup_requests');
    }
};
