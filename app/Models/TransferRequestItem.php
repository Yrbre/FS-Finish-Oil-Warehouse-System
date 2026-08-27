<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferRequestItem extends Model
{
    use HasFactory;

    const STATUS_NEW       = 'new';
    const STATUS_APPROVED  = 'approved';
    const STATUS_REJECTED  = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'transfer_request_id',
        'item_id',
        'requested_perpackage',
        'requested_package',
        'requested_qty',
        'status',
        'rejected_by',
        'rejected_at',
        'reject_reason',
        'cancelled_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected $casts = [
        'requested_perpackage' => 'decimal:4',
        'requested_package'    => 'decimal:2',
        'requested_qty'        => 'decimal:2',
        'rejected_at'          => 'datetime',
        'cancelled_at'         => 'datetime',
    ];

    public function transferRequest()
    {
        return $this->belongsTo(TransferRequest::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function details()
    {
        return $this->hasMany(TransferRequestDetail::class);
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /** Masih bisa ditolak/dibatalkan tanpa mengembalikan stok. */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_NEW;
    }

    /**
     * Sudah disetujui — stok SUDAH dipotong. Pembatalan di titik ini
     * harus mengembalikan stok ke lot asal, dan hanya boleh oleh IMC.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /** Item yang batal tidak ikut dicetak di TTB. */
    public function isVoid(): bool
    {
        return in_array($this->status, [self::STATUS_REJECTED, self::STATUS_CANCELLED], true);
    }
}
