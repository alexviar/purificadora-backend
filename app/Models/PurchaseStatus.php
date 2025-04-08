<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * Get the supply purchases that have this status.
     */
    public function supplyPurchases(): HasMany
    {
        return $this->hasMany(SupplyPurchase::class, 'status_id');
    }
}
