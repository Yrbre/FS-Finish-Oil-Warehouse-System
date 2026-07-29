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
        Schema::table('item_locations', function (Blueprint $table) {
            $table->foreignId('demander_id')->nullable()->after('warehouse_id')->constrained('departments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_locations', function (Blueprint $table) {
            $table->dropForeign(['demander_id']);
            $table->dropColumn('demander_id');
        });
    }
};
