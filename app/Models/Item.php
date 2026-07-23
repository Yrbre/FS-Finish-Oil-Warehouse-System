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

    public function stockLadgers()
    {
        return $this->hasMany(StockLedger::class);
    }
}
