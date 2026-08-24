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
}
