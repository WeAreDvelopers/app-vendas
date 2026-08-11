<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    use BelongsToCompany;

    protected $table = 'listings';
    protected $guarded = [];
}
