<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'tag',
        'department_id',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function itemLocations()
    {
        return $this->hasMany(ItemLocation::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function incomingTransfers()
    {
        return $this->hasMany(TransferRequest::class, 'destination_warehouse_id');
    }

    public function isImc(): bool
    {
        return $this->department?->code === Department::IMC_CODE;
    }
}
