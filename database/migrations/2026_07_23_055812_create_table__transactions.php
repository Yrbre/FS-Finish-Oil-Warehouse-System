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
            $table->string('doc_type');
            $table->string('adj_type')->nullable();
            $table->date('trans_date')->index();
            $table->decimal('trans_qty', 15, 2);
            $table->decimal('bb_qty', 15, 2)->default(0);
            $table->decimal('in_qty', 15, 2)->default(0);
            $table->decimal('out_qty', 15, 2)->default(0);
            $table->decimal('eb_qty', 15, 2)->default(0);
            $table->string('vendor_lot')->nullable();
            $table->date('production_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->decimal('qty_unit', 15, 2)->nullable();
            $table->string('package')->nullable();
            $table->string('item_no')->nullable();
            $table->string('item_desc')->nullable();
            $table->string('item_uom')->nullable();
            $table->string('item_glclass')->nullable();
            $table->string('status')->default('NEW');
            $table->text('notes')->nullable();
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
