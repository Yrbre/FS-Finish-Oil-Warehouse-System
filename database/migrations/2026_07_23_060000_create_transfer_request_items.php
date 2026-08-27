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
        Schema::create('transfer_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_request_id')->constrained('transfer_requests')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained();

            // Staff memilih ukuran + jumlah package. requested_qty
            // adalah turunan, untuk laporan saja.
            $table->decimal('requested_perpackage', 15, 4);
            $table->decimal('requested_package', 15, 2);
            $table->decimal('requested_qty', 15, 2);

            // Status PER ITEM — satu item bisa ditolak/dibatalkan
            // tanpa memengaruhi item lain dalam request yang sama.
            // new -> approved -> cancelled (oleh IMC, sebelum TTB)
            //     -> rejected | cancelled (saat masih new)
            $table->string('status')->default('new')->index();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('reject_reason')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->timestamps();

            // Item yang sama boleh diminta dua kali dalam satu request
            // ASAL ukurannya berbeda (mis. drum 200kg dan pail 20kg).
            $table->unique(['transfer_request_id', 'item_id', 'requested_perpackage'], 'tr_items_unique');

            // Untuk menghitung reservasi stok request yang masih 'new'
            $table->index(['item_id', 'requested_perpackage', 'status'], 'tr_items_reservation_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_request_items');
    }
};
