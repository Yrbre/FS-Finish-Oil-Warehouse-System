<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferRequestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_request_item_id',
        'item_location_id',
        'source_warehouse_id',
        'vendor_lot',
        'receiving_lot',
        'exp_date',
        'production_date',
        'package',
        'qty_perpackage',
        'package_taken',
        'qty_taken',
        'remaining_weight',
        'remaining_package',
        'dest_item_location_id',
    ];

    protected $casts = [
        'exp_date'        => 'date',
        'production_date' => 'date',
        'qty_perpackage'  => 'decimal:4',
        'package_taken'   => 'decimal:2',
        'qty_taken'       => 'decimal:2',
        'remaining_weight'  => 'decimal:2',
        'remaining_package' => 'decimal:2',
    ];

    public function transferRequestItem()
    {
        return $this->belongsTo(TransferRequestItem::class);
    }

    /**
     * Lot asal tempat stok diambil.
     */
    public function itemLocation()
    {
        return $this->belongsTo(ItemLocation::class);
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    /**
     * Lot baru yang terbentuk di gudang tujuan setelah barang diterima.
     */
    public function destItemLocation()
    {
        return $this->belongsTo(ItemLocation::class, 'dest_item_location_id');
    }
}
