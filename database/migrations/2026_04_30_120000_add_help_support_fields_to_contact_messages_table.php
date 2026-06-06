<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('pickup_request_id')->nullable()->after('user_id')->constrained('pickup_requests')->nullOnDelete();
            $table->string('user_role')->nullable()->after('pickup_request_id');
            $table->string('type')->default('general')->after('user_role');
            $table->string('name')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['pickup_request_id']);
            $table->dropColumn(['user_id', 'pickup_request_id', 'user_role', 'type']);
        });
    }
};
