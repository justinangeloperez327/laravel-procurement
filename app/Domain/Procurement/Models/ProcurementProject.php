<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\AppItem;
use App\Domain\Procurement\Enums\ProcurementStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'fiscal_year_id',
        'app_item_id',
        'procurement_method_id',
        'reference_no',
        'title',
        'description',
        'procurement_category',
        'approved_budget',
        'funding_source',
        'status',
        'current_stage',
        'created_by',
        'procurement_officer_id',
        'target_start_date',
        'target_completion_date',
    ];

    protected function casts(): array
    {
        return [
            'procurement_category' => ProcurementCategory::class,
            'approved_budget' => 'decimal:2',
            'status' => ProcurementStatus::class,
            'target_start_date' => 'date',
            'target_completion_date' => 'date',
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

    public function appItem(): BelongsTo
    {
        return $this->belongsTo(AppItem::class);
    }

    public function procurementMethod(): BelongsTo
    {
        return $this->belongsTo(ProcurementMethod::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function procurementOfficer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'procurement_officer_id');
    }
}
