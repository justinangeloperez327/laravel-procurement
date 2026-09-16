<?php

namespace App\Domain\Planning\Models;

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketScoping extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'fiscal_year_id',
        'organizational_unit_id',
        'reference_no',
        'title',
        'procurement_category',
        'description',
        'market_findings',
        'recommended_strategy',
        'status',
        'prepared_by',
        'reviewed_by',
        'approved_by',
        'submitted_at',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'procurement_category' => ProcurementCategory::class,
            'status' => PlanningStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function sources(): HasMany
    {
        return $this->hasMany(MarketScopingSource::class);
    }
}
