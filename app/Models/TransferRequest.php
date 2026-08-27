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
        'destination_warehouse_id',
        'department_id',
        'expected_date',
        'notes',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approved_date',
        'shipped_at',
        'shipped_by',
        'print_count',
        'received_by',
        'received_at',
        'received_date',
    ];

    protected $casts = [
        'expected_date' => 'date',
        'approved_date' => 'date',
        'received_date' => 'date',
        'approved_at'   => 'datetime',
        'shipped_at'    => 'datetime',
        'received_at'   => 'datetime',
        'print_count'   => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(TransferRequestItem::class);
    }

    /** Item yang masih hidup — tidak ditolak/dibatalkan. */
    public function activeItems()
    {
        return $this->items()->whereNotIn('status', [
            TransferRequestItem::STATUS_REJECTED,
            TransferRequestItem::STATUS_CANCELLED,
        ]);
    }

    /**
     * Turunkan status header dari status item-itemnya.
     * Dipanggil setiap kali status item berubah.
     */
    public function syncStatusFromItems(): void
    {
        // Setelah TTB terbit, status header tidak lagi mengikuti item.
        if (in_array($this->status, [self::STATUS_IN_TRANSIT, self::STATUS_RECEIVED], true)) {
            return;
        }

        $items = $this->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $status = match (true) {
            $items->contains('status', TransferRequestItem::STATUS_APPROVED)
            => self::STATUS_APPROVED,
            $items->every(fn($i) => $i->status === TransferRequestItem::STATUS_REJECTED)
            => self::STATUS_REJECTED,
            $items->every(fn($i) => $i->isVoid())
            => self::STATUS_CANCELLED,
            default
            => self::STATUS_NEW,
        };

        if ($this->status !== $status) {
            $this->update(['status' => $status]);
        }
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
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


    public function receiptOfGoods()
    {
        return $this->hasOne(ReceiptOfGoods::class);
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
