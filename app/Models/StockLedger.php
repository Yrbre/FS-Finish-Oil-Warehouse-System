<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockLedger extends Model
{
    use HasFactory;

    protected $table = 'stock_ledger';

    // Jenis mutasi
    const DOC_PORC         = 'PORC';
    const DOC_CONS         = 'CONS';
    const DOC_ADJ          = 'ADJ';
    const DOC_TRANSFER_IN  = 'TRANSFER_IN';
    const DOC_TRANSFER_OUT = 'TRANSFER_OUT';

    // Asal record — jembatan ke tabel aslinya lewat ref_id
    const REF_TRANSACTION  = 'transaction';   // → transactions
    const REF_TRANSFER_IN  = 'transfer_in';   // → transfer_requests
    const REF_TRANSFER_OUT = 'transfer_out';  // → transfer_requests

    protected $fillable = [
        'item_id',
        'warehouse_id',
        'trans_date',
        'in_qty',
        'out_qty',
        'bb_qty',
        'eb_qty',
        'doc_type',
        'ref_type',
        'ref_id',
    ];

    protected $casts = [
        'trans_date' => 'date',
        'in_qty'     => 'decimal:2',
        'out_qty'    => 'decimal:2',
        'bb_qty'     => 'decimal:2',
        'eb_qty'     => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Jenis mutasi yang menambah stok.
     */
    public static function inTypes(): array
    {
        return [self::DOC_PORC, self::DOC_TRANSFER_IN];
    }

    /**
     * Jenis mutasi yang mengurangi stok.
     */
    public static function outTypes(): array
    {
        return [self::DOC_CONS, self::DOC_TRANSFER_OUT];
    }
}
