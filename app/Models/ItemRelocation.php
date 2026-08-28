<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemRelocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'from_item_location_id',
        'to_item_location_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'from_tag',
        'to_tag',
        'demander_id',
        'qty_perpackage',
        'package_moved',
        'qty_moved',
        'reason',
        'moved_by',
        'moved_at',
    ];

    protected $casts = [
        'qty_perpackage' => 'decimal:4',
        'package_moved'  => 'decimal:2',
        'qty_moved'      => 'decimal:2',
        'moved_at'       => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function fromLot()
    {
        return $this->belongsTo(ItemLocation::class, 'from_item_location_id');
    }

    public function toLot()
    {
        return $this->belongsTo(ItemLocation::class, 'to_item_location_id');
    }

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function demander()
    {
        return $this->belongsTo(Department::class, 'demander_id');
    }

    public function mover()
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
