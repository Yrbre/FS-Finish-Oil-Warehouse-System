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
        Schema::create('receipt_of_goods', function (Blueprint $table) {
            $table->id();

            // Format: 0001/IMC/VIII/2026
            $table->string('letter_number')->unique();
            $table->date('letter_date');

            // Satu request = satu tanda terima. Tidak ada terima
            // sebagian, jadi unique.
            $table->foreignId('transfer_request_id')->unique()
                ->constrained('transfer_requests')->onDelete('cascade');

            // User yang membuat & mencetak tanda terima ini.
            // Diisi otomatis dari user yang login, tidak dipilih manual.
            $table->foreignId('responsibility_id')->constrained('users')->restrictOnDelete();

            $table->string('photo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_of_goods');
    }
};
