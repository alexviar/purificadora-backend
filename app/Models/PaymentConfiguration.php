<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentConfiguration extends Model
{
    protected $fillable = [
        'stripe_enabled',
        'efectivo_enabled',
    ];
}
