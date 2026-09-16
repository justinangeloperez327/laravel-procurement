<?php

namespace App\Models;

use App\Enums\ProcurementCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PpmpItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'ppmp_id',
        'market_scoping_id',
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
        'recommended_procurement_method',
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
        ];
    }

    public function ppmp(): BelongsTo
    {
        return $this->belongsTo(Ppmp::class);
    }

    public function marketScoping(): BelongsTo
    {
        return $this->belongsTo(MarketScoping::class);
    }
}
