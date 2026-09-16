<?php

namespace App\Domain\Planning\Models;

use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Procurement\Models\ProcurementMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpmpItem extends Model
{
    protected $fillable = [
        'ppmp_id',
        'market_scoping_id',
        'recommended_procurement_method_id',
        'item_no',
        'title',
        'description',
        'procurement_category',
        'quantity',
        'unit',
        'estimated_unit_cost',
        'estimated_budget',
        'funding_source',
        'target_quarter',
        'remarks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'procurement_category' => ProcurementCategory::class,
            'quantity' => 'decimal:3',
            'estimated_unit_cost' => 'decimal:2',
            'estimated_budget' => 'decimal:2',
            'target_quarter' => 'integer',
        ];
    }

    /** @return BelongsTo<Ppmp, $this> */
    public function ppmp(): BelongsTo
    {
        return $this->belongsTo(Ppmp::class);
    }

    /** @return BelongsTo<MarketScoping, $this> */
    public function marketScoping(): BelongsTo
    {
        return $this->belongsTo(MarketScoping::class);
    }

    /** @return BelongsTo<ProcurementMethod, $this> */
    public function recommendedProcurementMethod(): BelongsTo
    {
        return $this->belongsTo(ProcurementMethod::class, 'recommended_procurement_method_id');
    }
}
