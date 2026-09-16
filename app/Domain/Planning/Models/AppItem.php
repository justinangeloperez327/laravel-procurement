<?php

namespace App\Domain\Planning\Models;

use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Procurement\Models\ProcurementMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppItem extends Model
{
    protected $fillable = [
        'annual_procurement_plan_id',
        'ppmp_item_id',
        'procurement_method_id',
        'app_item_no',
        'title',
        'description',
        'procurement_category',
        'is_early_procurement_activity',
        'bid_evaluation_criteria',
        'estimated_budget',
        'funding_source',
        'schedule_start',
        'schedule_end',
        'procurement_strategy_tools',
        'remarks',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'procurement_category' => ProcurementCategory::class,
            'is_early_procurement_activity' => 'boolean',
            'estimated_budget' => 'decimal:2',
            'schedule_start' => 'date',
            'schedule_end' => 'date',
            'procurement_strategy_tools' => 'array',
        ];
    }

    /** @return BelongsTo<AnnualProcurementPlan, $this> */
    public function annualProcurementPlan(): BelongsTo
    {
        return $this->belongsTo(AnnualProcurementPlan::class);
    }

    /** @return BelongsTo<PpmpItem, $this> */
    public function ppmpItem(): BelongsTo
    {
        return $this->belongsTo(PpmpItem::class);
    }

    /** @return BelongsTo<ProcurementMethod, $this> */
    public function procurementMethod(): BelongsTo
    {
        return $this->belongsTo(ProcurementMethod::class);
    }
}
