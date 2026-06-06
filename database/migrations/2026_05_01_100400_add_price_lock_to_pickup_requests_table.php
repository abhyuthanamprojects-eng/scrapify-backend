<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->timestamp('price_locked_at')->nullable()->after('final_amount');
            $table->foreignId('final_amount_modified_by')->nullable()->after('price_locked_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pickup_requests', function (Blueprint $table) {
            $table->dropForeign(['final_amount_modified_by']);
            $table->dropColumn(['price_locked_at', 'final_amount_modified_by']);
        });
    }
};
