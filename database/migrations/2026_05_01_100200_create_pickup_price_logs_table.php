<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_price_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pickup_request_id')->constrained('pickup_requests')->cascadeOnDelete();
            $table->decimal('old_amount', 12, 2)->nullable();
            $table->decimal('new_amount', 12, 2);
            $table->foreignId('modified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('modified_by_type')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('pickup_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_price_logs');
    }
};
