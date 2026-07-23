<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
    ];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function transferRequest()
    {
        return $this->hasMany(TransferRequest::class);
    }
}
