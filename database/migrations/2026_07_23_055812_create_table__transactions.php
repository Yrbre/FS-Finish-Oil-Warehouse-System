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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Snapshot pemilik stok saat transaksi terjadi.
            $table->foreignId('demander_id')->nullable()->constrained('departments')->nullOnDelete();

            // Lot yang dikoreksi (khusus ADJ). Sebelumnya dikirim
            // service tapi kolomnya tidak ada, jadi jejaknya hilang.
            $table->foreignId('item_location_id')->nullable()->constrained('item_locations')->nullOnDelete();

            $table->string('doc_type');             // PORC | CONS | ADJ | DISPOSAL
            $table->string('adj_type')->nullable(); // in | out
            $table->date('trans_date')->index();

            $table->decimal('trans_qty', 15, 2);
            $table->decimal('bb_qty', 15, 2)->default(0);
            $table->decimal('in_qty', 15, 2)->default(0);
            $table->decimal('out_qty', 15, 2)->default(0);
            $table->decimal('eb_qty', 15, 2)->default(0);

            // Diisi hanya untuk PORC (input berbasis package).
            // CONS/ADJ/DISPOSAL berbasis kg, dibiarkan null.
            $table->decimal('qty_perpackage', 15, 4)->nullable();
            $table->decimal('qty_package', 15, 2)->nullable();

            $table->string('vendor_lot')->nullable();
            $table->string('receiving_lot')->nullable();
            $table->date('production_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->string('package')->nullable();

            // Snapshot data item, supaya riwayat tetap terbaca meski
            // master item berubah kemudian.
            $table->string('item_no')->nullable();
            $table->string('item_desc')->nullable();
            $table->string('item_uom')->nullable();
            $table->string('item_glclass')->nullable();

            $table->string('status')->default('NEW');
            $table->text('notes')->nullable();

            // Jejak edit PORC.
            $table->timestamp('edited_at')->nullable();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('edit_reason')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
            $table->index(['item_id', 'warehouse_id', 'trans_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
