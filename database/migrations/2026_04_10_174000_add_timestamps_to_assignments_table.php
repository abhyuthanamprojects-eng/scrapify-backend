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
        Schema::table('assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('assignments', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('assignments', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('assigned_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'completed_at']);
        });
    }
};
