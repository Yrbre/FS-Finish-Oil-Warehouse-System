<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_no',
        'item_desc',
        'item_uom',
        'item_glclass',
        'min_stock',
    ];

    protected $casts = [
        'min_stock' => 'decimal:2',
    ];

    public function itemLocations()
    {
        return $this->hasMany(ItemLocation::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function stockLedgers()
    {
        return $this->hasMany(StockLedger::class);
    }

    public function isMonitored(): bool
    {
        return $this->min_stock !== null && (float) $this->min_stock > 0;
    }

    public function minimumStocks()
    {
        return $this->hasMany(MinimumStock::class);
    }

    /**
     * Ambang minimum untuk sebuah department.
     *
     * Pengaturan per department menimpa nilai global di items —
     * kalau belum diatur, dipakai min_stock item sebagai default.
     * Null berarti item ini tidak dipantau untuk department itu.
     */
    public function minStockFor(int $departmentId): ?float
    {
        $specific = $this->minimumStocks
            ->firstWhere(fn($m) => $m->department_id === $departmentId && $m->is_active);

        if ($specific) {
            return (float) $specific->min_stock;
        }

        return $this->min_stock !== null ? (float) $this->min_stock : null;
    }
}
