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
        Schema::create('stock_ladger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->date('trans_date');
            $table->decimal('bb_qty', 15, 2)->default(0);
            $table->decimal('in_qty', 15, 2)->default(0);
            $table->decimal('out_qty', 15, 2)->default(0);
            $table->decimal('eb_qty', 15, 2)->default(0);
            $table->string('doc_type')->index();
            $table->string('ref_type')->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();
            $table->index(['item_id', 'warehouse_id', 'trans_date'], 'stock_ledger_item_wh_date_idx');
            $table->index(['ref_type', 'ref_id']);
            $table->index(['warehouse_id', 'trans_date', 'doc_type'], 'stock_ledger_report_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ladger');
    }
};
