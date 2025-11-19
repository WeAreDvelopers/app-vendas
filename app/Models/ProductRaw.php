<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRaw extends Model
{
    protected $table = 'products_raw';
    protected $guarded = [];

    protected $casts = [
        'extra' => 'array',
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function supplierImport(): BelongsTo
    {
        return $this->belongsTo(SupplierImport::class);
    }
}
