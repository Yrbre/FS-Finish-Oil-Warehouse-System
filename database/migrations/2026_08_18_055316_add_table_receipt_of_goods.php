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
            $table->string('letter_number')->nullable();
            $table->date('letter_date')->nullable();
            $table->foreignId('transfer_request_id')->constrained('transfer_requests')->onDelete('cascade');
            $table->foreignId('responsibility_id')->constrained('users')->onDelete('cascade');
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
