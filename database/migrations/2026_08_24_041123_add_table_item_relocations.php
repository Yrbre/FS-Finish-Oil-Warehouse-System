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
        Schema::create('item_relocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained();

            // Lot asal dan lot hasil pemindahan
            $table->foreignId('from_item_location_id')->constrained('item_locations');
            $table->foreignId('to_item_location_id')->nullable()->constrained('item_locations');

            $table->foreignId('from_warehouse_id')->constrained('warehouses');
            $table->foreignId('to_warehouse_id')->constrained('warehouses');

            // Snapshot tag/lokasi saat dipindah — nama gudang bisa berubah nanti
            $table->string('from_tag')->nullable();
            $table->string('to_tag')->nullable();

            // Pemilik tidak berubah, disimpan sebagai bukti
            $table->foreignId('demander_id')->constrained('departments');

            $table->decimal('qty_perpackage', 15, 4);
            $table->decimal('package_moved', 15, 2);
            $table->decimal('qty_moved', 15, 2);

            $table->text('reason')->nullable();
            $table->foreignId('moved_by')->constrained('users');
            $table->timestamp('moved_at');

            $table->timestamps();

            $table->index(['item_id', 'moved_at']);
            $table->index('from_item_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_relocations');
    }
};
