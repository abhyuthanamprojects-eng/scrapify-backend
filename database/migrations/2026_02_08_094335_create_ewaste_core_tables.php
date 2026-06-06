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
        // Categories
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // Localized name
            $table->string('slug')->unique();
            $table->string('image_path')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // Attributes (e.g., Condition, Brand, Age)
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->enum('type', ['select', 'radio', 'checkbox', 'text', 'number'])->default('select');
            $table->string('unit')->nullable(); // e.g., kg, pcs
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Attribute Options (e.g., Working, Non-Working, Scrap)
        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->json('value');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Category Attributes (Pivot)
        Schema::create('category_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained()->onDelete('cascade');
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        // Pricing Rules
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            // Optional: link to specific attribute option (e.g., Condition: Working)
            $table->foreignId('attribute_option_id')->nullable()->constrained('attribute_options')->onDelete('cascade'); 
            $table->decimal('base_price', 10, 2); // Price per unit
            $table->string('currency')->default('INR');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('category_attributes');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('categories');
    }
};
