<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TransactionProduct extends Pivot
{
    protected $table = 'transaction_products';

    protected $fillable = [
        'transaction_id',
        'product_id',
        'quantity',
        'unit_amount',
    ];
}
