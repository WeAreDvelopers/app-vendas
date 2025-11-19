<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    protected $guarded = [];

    protected $casts = [
        'row_data' => 'array',
    ];

    public function supplierImport(): BelongsTo
    {
        return $this->belongsTo(SupplierImport::class);
    }
}
