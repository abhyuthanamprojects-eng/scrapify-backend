<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('category_type_id')->nullable()->after('id')->constrained('category_types')->onDelete('restrict');
        });

        // Drop the old type enum column
        Schema::table('categories', function (Blueprint $table) {
            // Note: SQLite might have trouble dropping enums in older Laravel versions, 
            // but MySQL/Postgres handles this fine. Assuming MySQL based on typical Laravel usage.
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->enum('type', ['electronics', 'metal', 'plastic'])->default('electronics')->after('slug');
        });


        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['category_type_id']);
            $table->dropColumn('category_type_id');
        });

        Schema::dropIfExists('category_types');
    }
};
