<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function mapping(): HasOne
    {
        return $this->hasOne(SupplierMapping::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(SupplierImport::class);
    }
}
