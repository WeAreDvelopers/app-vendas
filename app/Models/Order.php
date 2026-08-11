<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use BelongsToCompany;

    protected $table = 'orders';
    protected $guarded = [];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'date_closed' => 'datetime',
        'payload' => 'array',
    ];
}
