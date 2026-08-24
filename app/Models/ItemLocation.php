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
        'exp_date',
        'qty_perpackage',
        'qty_package',
        'qty_weight',
        'initial_weight',
        'package',
        'type',
        'received_date',
        'exp_by_receiving_at',
        'is_warehouse_stock',
        'disposed_at',
        'disposed_by',
        'disposal_reason',
    ];

    protected $casts = [
        'production_date'     => 'date',
        'exp_date'            => 'date',
        'exp_by_receiving_at' => 'date',
        'received_date'       => 'date',
        'disposed_at'         => 'datetime',
        'qty_perpackage'      => 'decimal:4',
        'qty_package'         => 'decimal:2',
        'qty_weight'          => 'decimal:2',
        'initial_weight' => 'decimal:2',
        'is_warehouse_stock'  => 'boolean',
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

    public function disposer()
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }

    /* ---------------- Accessor ---------------- */

    /**
     * Jumlah package dari sisa berat. Dihitung saat tampil, TIDAK
     * disimpan — kalau disimpan, tiap CONS menambah galat pembulatan.
     */
    public function getQtyPackageDisplayAttribute(): float
    {
        $per = (float) $this->qty_perpackage;

        return $per > 0 ? round((float) $this->qty_weight / $per, 2) : 0.0;
    }

    public function getIsDisposedAttribute(): bool
    {
        return $this->disposed_at !== null;
    }

    /**
     * Hanya lot yang masih ada stoknya.
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('qty_weight', '>', 0)->whereNull('disposed_at');
    }

    /**
     * Urutan FEFO — yang paling dekat expired diambil duluan.
     * Lot yang sudah lewat expired ikut terambil (tanggalnya paling kecil).
     */
    public function scopeFefo(Builder $query): Builder
    {
        return $query
            ->orderByRaw('exp_date IS NULL')
            ->orderBy('exp_date', 'asc')
            ->orderBy('received_date', 'asc')
            ->orderBy('id', 'asc');
    }

    /** WAJIB di semua alokasi — stok department tidak boleh diambil department lain. */
    public function scopeOwnedBy(Builder $query, int $demanderId): Builder
    {
        return $query->where('demander_id', $demanderId);
    }

    /** Transfer memindahkan package utuh, ukuran harus cocok persis. */
    public function scopeOfPackageSize(Builder $query, float $perPackage): Builder
    {
        return $query->where('qty_perpackage', $perPackage);
    }

    /**
     * Lot sudah pernah dimutasi (transfer keluar, CONS, atau ADJ).
     * Kalau sudah, qty PORC-nya tidak boleh diedit lagi — koreksi
     * selisih setelah ini adalah urusan ADJ.
     */
    public function isTouched(): bool
    {
        return (float) $this->qty_weight !== (float) $this->initial_weight;
    }

    /**
     * Berapa kg sudah keluar dari lot ini.
     */
    public function getConsumedWeightAttribute(): float
    {
        return round((float) $this->initial_weight - (float) $this->qty_weight, 2);
    }
}
