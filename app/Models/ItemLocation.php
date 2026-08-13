<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemLocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'demander_id',
        'vendor_lot',
        'receiving_lot',
        'production_date',
        'exp_date',
        'qty_weight',
        'qty_unit',
        'package',
        'type',
        'received_date',
        'is_warehouse_stock',
    ];

    protected $casts = [
        'production_date'    => 'date',
        'exp_date'           => 'date',
        'received_date'      => 'date',
        'qty_weight'         => 'decimal:2',
        'qty_unit'           => 'decimal:2',
        'is_warehouse_stock' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function demander()
    {
        return $this->belongsTo(Department::class, 'demander_id');
    }

    /**
     * Hanya lot yang masih ada stoknya.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('qty_weight', '>', 0);
    }

    /**
     * Urutan FEFO — yang paling dekat expired diambil duluan.
     * Lot yang sudah lewat expired ikut terambil (tanggalnya paling kecil).
     */
    public function scopeFefo(Builder $query): Builder
    {
        return $query->orderBy('exp_date', 'asc');
    }
}
