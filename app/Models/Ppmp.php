<?php

namespace App\Models;

use App\Enums\PlanningStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ppmp extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'fiscal_year_id',
        'organizational_unit_id',
        'reference_no',
        'title',
        'status',
        'prepared_by',
        'submitted_at',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
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

    public function items(): HasMany
    {
        return $this->hasMany(PpmpItem::class);
    }
}
