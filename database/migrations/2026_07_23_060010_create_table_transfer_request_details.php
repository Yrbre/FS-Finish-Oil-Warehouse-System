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
            $table->string('receiving_lot')->nullable();
            $table->date('exp_date')->nullable();
            $table->date('production_date')->nullable();
            $table->string('package')->nullable();

            // qty_taken = package_taken x qty_perpackage
            $table->decimal('qty_perpackage', 15, 4);
            $table->decimal('package_taken', 15, 2);
            $table->decimal('qty_taken', 15, 2);

            // FK ini sebelumnya tidak ada, jadi bisa menunjuk lot yang
            // sudah terhapus tanpa ketahuan.
            $table->foreignId('dest_item_location_id')->nullable()
                ->constrained('item_locations')->nullOnDelete();

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
