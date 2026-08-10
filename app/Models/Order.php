<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use BelongsToCompany;

    protected $table = 'orders';
    protected $guarded = [];
}
