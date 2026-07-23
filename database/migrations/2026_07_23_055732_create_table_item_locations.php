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
            $table->string('vendor_lot')->nullable();
            $table->date('production_date')->nullable();
            $table->date('exp_date')->nullable()->index();
            $table->decimal('qty_unit', 15, 2)->nullable();
            $table->decimal('qty_weight', 15, 2)->nullable();
            $table->string('package')->nullable();
            $table->string('type')->nullable();
            $table->date('received_date')->nullable();
            $table->boolean('is_warehouse_stock')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['item_id', 'warehouse_id']);
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
