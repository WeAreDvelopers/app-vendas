<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierImport extends Model {
  protected $table = 'supplier_imports';
  protected $guarded = [];

  protected $casts = [
    'mapping' => 'array',
  ];

  public function supplier(): BelongsTo
  {
    return $this->belongsTo(Supplier::class);
  }

  public function errors(): HasMany
  {
    return $this->hasMany(ImportError::class);
  }

  public function productsRaw(): HasMany
  {
    return $this->hasMany(ProductRaw::class);
  }
}
