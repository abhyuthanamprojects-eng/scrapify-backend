<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->after('pickup_boy_id')->constrained('warehouses')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->string('assigned_by_type')->nullable()->after('assigned_by'); // admin/warehouse/channel_partner
            $table->text('remarks')->nullable()->after('assigned_by_type');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['assigned_by']);
            $table->dropColumn(['warehouse_id', 'assigned_by', 'assigned_by_type', 'remarks']);
        });
    }
};
