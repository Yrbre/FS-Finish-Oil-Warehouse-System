<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transactions';

    // Jenis transaksi input manual user
    const DOC_PORC = 'PORC'; // pemasukan dari vendor
    const DOC_CONS = 'CONS'; // pengeluaran / pemakaian
    const DOC_ADJ  = 'ADJ';  // koreksi kesalahan input

    // Arah adjustment
    const ADJ_IN  = 'in';
    const ADJ_OUT = 'out';

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'doc_type',
        'adj_type',
        'trans_date',
        'trans_qty',
        'in_qty',
        'out_qty',
        'bb_qty',
        'eb_qty',
        'vendor_lot',
        'receiving_lot',
        'production_date',
        'exp_date',
        'qty_unit',
        'package',
        'item_no',
        'item_desc',
        'item_uom',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'trans_date'      => 'date',
        'production_date' => 'date',
        'exp_date'        => 'date',
        'trans_qty'       => 'decimal:2',
        'in_qty'          => 'decimal:2',
        'out_qty'         => 'decimal:2',
        'bb_qty'          => 'decimal:2',
        'eb_qty'          => 'decimal:2',
        'qty_unit'        => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
