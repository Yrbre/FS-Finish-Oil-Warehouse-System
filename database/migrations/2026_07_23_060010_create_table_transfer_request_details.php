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
        Schema::create('transfer_request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_request_id')->constrained('transfer_requests')->cascadeOnDelete();
            $table->foreignId('item_location_id')->constrained('item_locations');
            $table->foreignId('source_warehouse_id')->constrained('warehouses');
            $table->string('vendor_lot')->nullable();
            $table->date('exp_date')->nullable();
            $table->date('production_date')->nullable();
            $table->string('package')->nullable();
            $table->decimal('qty_taken', 15, 2);
            $table->decimal('qty_unit', 15, 2)->nullable();
            $table->unsignedBigInteger('dest_item_location_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_request_details');
    }
};
