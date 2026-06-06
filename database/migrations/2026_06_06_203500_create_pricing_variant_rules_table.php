<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pricing_variant_rules')) {
            return;
        }

        Schema::create('pricing_variant_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('variant_key');
            $table->json('option_values');
            $table->decimal('base_price', 12, 2);
            $table->string('source_column', 8)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['category_id', 'variant_key'], 'pricing_variant_category_key_unique');
            $table->index(['category_id', 'status'], 'pricing_variant_category_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_variant_rules');
    }
};
