<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseDetailStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Get the supply purchase details that have this status.
     */
    public function supplyPurchaseDetails(): HasMany
    {
        return $this->hasMany(SupplyPurchaseDetail::class, 'status_id');
    }
}
