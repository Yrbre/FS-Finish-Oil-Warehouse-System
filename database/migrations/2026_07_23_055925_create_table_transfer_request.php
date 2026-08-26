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
            $table->string('transfer_code')->unique(); // TRNS-yymmdd001

            $table->foreignId('item_id')->constrained()->cascadeOnDelete();

            // Staff memilih ukuran kemasan + jumlah package.
            // requested_qty adalah turunan (package x perpackage),
            // disimpan untuk laporan dan tampilan saja.
            $table->decimal('requested_perpackage', 15, 4);
            $table->decimal('requested_package', 15, 2);
            $table->decimal('requested_qty', 15, 2);

            // Gudang asal tidak dipilih user — ditentukan sistem lewat
            // FEFO di gudang IMC, terbatas pada lot milik department
            // pemohon (demander_id).
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();

            $table->date('expected_date');
            $table->text('notes')->nullable();

            // new -> approved -> in_transit -> received
            // cabang: rejected | cancelled
            $table->string('status')->default('new')->index();

            $table->foreignId('requested_by')->constrained('users');

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->date('approved_date')->nullable(); // boleh backdate

            // Tanda terima barang dibuat saat IMC mengirim. Nomor &
            // dokumennya ada di tabel receipt_of_goods — di sini hanya
            // jejak status pengirimannya.
            $table->timestamp('shipped_at')->nullable();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('print_count')->default(0);

            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->date('received_date')->nullable(); // boleh backdate

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
