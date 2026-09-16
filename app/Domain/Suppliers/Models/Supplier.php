<?php

namespace App\Domain\Suppliers\Models;

use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'organization_id',
        'supplier_code',
        'legal_name',
        'trade_name',
        'tin',
        'philgeps_registration_no',
        'email',
        'phone',
        'address_line',
        'city_municipality',
        'province',
        'country_code',
        'status',
    ];

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return HasMany<SupplierDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(SupplierDocument::class);
    }
}
