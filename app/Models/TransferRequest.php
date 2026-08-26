<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class TransferRequest extends Model
{
    use HasFactory, SoftDeletes;

    // Alur status: new → in_transit → received
    // Cabang: new → rejected (oleh IMC) | new → cancelled (oleh requester)
    const STATUS_NEW        = 'new';
    const STATUS_APPROVED   = 'approved';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_RECEIVED   = 'received';
    const STATUS_REJECTED   = 'rejected';
    const STATUS_CANCELLED  = 'cancelled';

    /**
     * Transfer selalu diambil dari gudang milik department dengan kode ini.
     * Kalau suatu saat kebijakan berubah (misal ada 2 department sumber),
     * ubah cukup di sini — tidak perlu ubah service.
     */
    const SOURCE_DEPARTMENT_CODE = 'IMC';


    protected $fillable = [
        'transfer_code',
        'item_id',
        'requested_perpackage',
        'requested_package',
        'requested_qty',
        'destination_warehouse_id',
        'department_id',
        'expected_date',
        'notes',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approved_date',
        'surat_jalan_number',
        'surat_jalan_date',
        'shipped_at',
        'shipped_by',
        'print_count',
        'received_by',
        'received_at',
        'received_date',
        'rejected_by',
        'rejected_at',
        'reject_reason',
        'cancelled_by',
        'cancelled_at',
    ];

    protected $casts = [
        'requested_perpackage' => 'decimal:4',
        'requested_package'    => 'decimal:2',
        'requested_qty'        => 'decimal:2',
        'expected_date'        => 'date',
        'approved_date'        => 'date',
        'surat_jalan_date'     => 'date',
        'received_date'        => 'date',
        'approved_at'          => 'datetime',
        'shipped_at'           => 'datetime',
        'received_at'          => 'datetime',
        'rejected_at'          => 'datetime',
        'cancelled_at'         => 'datetime',
        'print_count'          => 'integer',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function details()
    {
        return $this->hasMany(TransferRequestDetail::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function receiptOfGoods()
    {
        return $this->hasOne(ReceiptOfGoods::class);
    }

    /**
     * Request hanya bisa dibatalkan requester selama belum ada
     * approval maupun penolakan.
     */
    public function isCancellable(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    /**
     * Surat jalan hanya bisa dicetak setelah approve. Cetak pertama → in_transit.
     */
    public function isShippable(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isReceivable(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    /** Sudah pernah dicetak — cetak berikutnya tidak mengubah status. */
    public function isPrinted(): bool
    {
        return (int) $this->print_count > 0;
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    /**
     * Generate kode dengan format TRNS-yymmdd001, reset tiap hari.
     */
    public static function generateTransferCode(): string
    {
        $prefix = 'TRNS-';
        $date   = now()->format('ymd');

        return DB::transaction(function () use ($prefix, $date) {
            $lastRecord = self::withTrashed()
                ->where('transfer_code', 'like', $prefix . $date . '%')
                ->orderBy('transfer_code', 'desc')
                ->lockForUpdate()
                ->first();

            $newNumber = $lastRecord
                ? ((int) substr($lastRecord->transfer_code, strlen($prefix . $date))) + 1
                : 1;

            return $prefix . $date . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        });
    }
}
