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
        Schema::create('item_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');

            // PEMILIK STOK — wajib, ditentukan sejak PORC dan ikut
            // berpindah bersama barang saat transfer. Lot tanpa pemilik
            // = stok yang tidak bisa diakses siapapun, jadi NOT NULL.
            $table->foreignId('demander_id')->constrained('departments')->restrictOnDelete();

            $table->string('vendor_lot')->nullable();
            $table->string('receiving_lot')->nullable()->index();
            $table->date('production_date')->nullable();
            $table->date('exp_date')->nullable()->index();

            // qty_weight = qty_package × qty_perpackage, SELALU hasil perkalian.
            // Di gudang staff, qty_package TIDAK di-update saat CONS —
            // ditampilkan sebagai qty_weight ÷ qty_perpackage (2 desimal).
            $table->decimal('qty_perpackage', 15, 4);
            $table->decimal('qty_package', 15, 2);
            $table->decimal('qty_weight', 15, 2);

            // Berat saat lot pertama kali dibuat. Tidak pernah berubah
            // kecuali PORC-nya diedit. Dipakai untuk mendeteksi apakah
            // lot sudah tersentuh mutasi (transfer/CONS/ADJ) — kalau
            // sudah, qty PORC tidak boleh diubah lagi.
            $table->decimal('initial_weight', 15, 2);

            $table->string('package')->nullable();
            $table->string('type')->nullable();
            $table->date('received_date')->nullable();
            $table->date('exp_by_receiving_at')->nullable();
            $table->boolean('is_warehouse_stock')->default(false);

            $table->timestamp('disposed_at')->nullable();
            $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('disposal_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // FEFO selalu menyaring: item + demander + ukuran package
            $table->index(['item_id', 'demander_id', 'qty_perpackage'], 'item_locations_fefo_idx');
            $table->index(['item_id', 'warehouse_id']);
            $table->index(['demander_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_locations');
    }
};
