<?php

namespace App\Domain\Planning\Models;

use App\Domain\Suppliers\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketScopingSource extends Model
{
    protected $fillable = [
        'market_scoping_id',
        'supplier_id',
        'vendor_name',
        'indicative_price',
        'lead_time_days',
        'source_type',
        'source_reference',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'indicative_price' => 'decimal:2',
            'lead_time_days' => 'integer',
        ];
    }

    /** @return BelongsTo<MarketScoping, $this> */
    public function marketScoping(): BelongsTo
    {
        return $this->belongsTo(MarketScoping::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
