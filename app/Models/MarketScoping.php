<?php

namespace App\Models;

use App\Enums\PlanningStatus;
use App\Enums\ProcurementCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'market_scoping_suppliers')
            ->withPivot(['vendor_name', 'indicative_price', 'lead_time_days', 'notes'])
            ->withTimestamps();
    }
}
