<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Procurement\Enums\ProcurementRoundStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementRound extends Model
{
    protected $fillable = [
        'procurement_project_id',
        'procurement_method_id',
        'round_no',
        'reference_no',
        'status',
        'failure_reason',
        'pre_procurement_at',
        'posting_started_at',
        'bid_opening_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'round_no' => 'integer',
            'status' => ProcurementRoundStatus::class,
            'pre_procurement_at' => 'datetime',
            'posting_started_at' => 'datetime',
            'bid_opening_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProcurementProject, $this> */
    public function procurementProject(): BelongsTo
    {
        return $this->belongsTo(ProcurementProject::class);
    }

    /** @return BelongsTo<ProcurementMethod, $this> */
    public function procurementMethod(): BelongsTo
    {
        return $this->belongsTo(ProcurementMethod::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
