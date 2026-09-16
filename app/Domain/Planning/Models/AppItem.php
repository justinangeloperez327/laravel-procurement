<?php

namespace App\Domain\Planning\Models;

use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Procurement\Models\ProcurementMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'annual_procurement_plan_id',
        'ppmp_item_id',
        'procurement_method_id',
        'app_item_no',
        'title',
        'description',
        'procurement_category',
        'estimated_budget',
        'funding_source',
        'schedule_start',
        'schedule_end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'procurement_category' => ProcurementCategory::class,
            'estimated_budget' => 'decimal:2',
            'schedule_start' => 'date',
            'schedule_end' => 'date',
        ];
    }

    public function annualProcurementPlan(): BelongsTo
    {
        return $this->belongsTo(AnnualProcurementPlan::class);
    }

    public function ppmpItem(): BelongsTo
    {
        return $this->belongsTo(PpmpItem::class);
    }

    public function procurementMethod(): BelongsTo
    {
        return $this->belongsTo(ProcurementMethod::class);
    }
}
