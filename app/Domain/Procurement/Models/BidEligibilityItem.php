<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Procurement\Enums\EligibilityCriterionResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidEligibilityItem extends Model
{
    protected $fillable = [
        'bid_eligibility_evaluation_id',
        'sequence_no',
        'criterion',
        'result',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'result' => EligibilityCriterionResult::class,
        ];
    }

    /** @return BelongsTo<BidEligibilityEvaluation, $this> */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(BidEligibilityEvaluation::class, 'bid_eligibility_evaluation_id');
    }
}
