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
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_partner_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type'); // pickup_boy, warehouse
            $table->unsignedBigInteger('entity_id')->nullable(); 
            $table->string('request_type'); // create, update, status_change
            $table->json('payload');
            $table->json('attachments')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('admin_remarks')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
