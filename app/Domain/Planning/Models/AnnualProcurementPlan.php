<?php

namespace App\Domain\Planning\Models;

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Planning\Enums\AnnualProcurementPlanType;
use App\Domain\Planning\Enums\PlanningStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnnualProcurementPlan extends Model
{
    protected $fillable = [
        'organization_id',
        'fiscal_year_id',
        'reference_no',
        'app_type',
        'version',
        'status',
        'prepared_by',
        'recommended_by',
        'recommended_at',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'app_type' => AnnualProcurementPlanType::class,
            'version' => 'integer',
            'status' => PlanningStatus::class,
            'recommended_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<FiscalYear, $this> */
    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    /** @return HasMany<AppItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(AppItem::class);
    }
}
