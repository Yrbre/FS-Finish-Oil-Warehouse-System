<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_code')->unique(); // format TRNS-yymmdd001

            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('requested_qty', 15, 2);

            // Tidak ada source_warehouse_id — gudang asal ditentukan sistem
            // otomatis lewat FEFO lintas warehouse saat approval.
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->cascadeOnDelete();

            // Untuk reporting saja (department mana yang paling sering request),
            // tidak dipakai untuk otorisasi.
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();

            $table->date('expected_date'); // tanggal barang seharusnya sampai
            $table->text('notes')->nullable();

            // new, in_transit, received, rejected, cancelled
            $table->string('status')->default('new')->index();

            $table->foreignId('requested_by')->constrained('users');

            // Approval IMC (1 gerbang saja untuk seluruh request)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('approved_date')->nullable(); // tanggal efektif stok keluar (bisa backdate)

            // Konfirmasi terima di gudang tujuan
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->date('received_date')->nullable(); // tanggal efektif stok masuk (bisa backdate)

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('reject_reason')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_requests');
    }
};
