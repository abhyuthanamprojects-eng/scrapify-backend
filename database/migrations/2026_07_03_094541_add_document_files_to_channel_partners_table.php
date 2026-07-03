<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channel_partners', function (Blueprint $table) {
            $table->string('aadhaar_file')->nullable()->after('aadhaar_number');
            $table->string('pan_file')->nullable()->after('pan_number');
            $table->string('gst_file')->nullable()->after('gst_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channel_partners', function (Blueprint $table) {
            $table->dropColumn(['aadhaar_file', 'pan_file', 'gst_file']);
        });
    }
};
