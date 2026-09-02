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


    public function minimumStocks()
    {
        return $this->hasMany(MinimumStock::class);
    }

    /**
     * Ambang minimum untuk sebuah department.
     *
     * Hanya dari tabel minimum_stocks — tiap department mengatur
     * sendiri. Null berarti item ini tidak dipantau untuk
     * department tersebut.
     */
    public function minStockFor(int $departmentId): ?float
    {
        $setting = $this->minimumStocks
            ->firstWhere(fn($m) => $m->department_id === $departmentId && $m->is_active);

        return $setting ? (float) $setting->min_stock : null;
    }
}
