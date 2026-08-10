<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportError extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'row_data' => 'array',
    ];

    public function supplierImport(): BelongsTo
    {
        return $this->belongsTo(SupplierImport::class);
    }
}
