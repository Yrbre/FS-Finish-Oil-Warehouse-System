<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferRequestDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_request_id',
        'item_location_id',
        'source_warehouse_id',
        'vendor_lot',
        'exp_date',
        'production_date',
        'package',
        'qty_taken',
        'qty_unit',
        'dest_item_location_id',
    ];

    protected $casts = [
        'exp_date'        => 'date',
        'production_date' => 'date',
        'qty_taken'       => 'decimal:2',
        'qty_unit'        => 'decimal:2',
    ];

    public function transferRequest()
    {
        return $this->belongsTo(TransferRequest::class);
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
