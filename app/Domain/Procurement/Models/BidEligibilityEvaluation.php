<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Procurement\Enums\EligibilityEvaluationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BidEligibilityEvaluation extends Model
{
    protected $fillable = [
        'procurement_round_id',
        'bid_submission_id',
        'status',
        'evaluated_at',
        'evaluated_by',
        'decision_remarks',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => EligibilityEvaluationStatus::class,
            'evaluated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProcurementRound, $this> */
    public function procurementRound(): BelongsTo
    {
        return $this->belongsTo(ProcurementRound::class);
    }

    /** @return BelongsTo<BidSubmission, $this> */
    public function bidSubmission(): BelongsTo
    {
        return $this->belongsTo(BidSubmission::class);
    }

    /** @return BelongsTo<User, $this> */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<BidEligibilityItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(BidEligibilityItem::class)->orderBy('sequence_no');
    }
}
