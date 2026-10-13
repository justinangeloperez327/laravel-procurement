<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Procurement\Enums\BidSubmissionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BidSubmission extends Model
{
    protected $fillable = [
        'procurement_round_id',
        'bidder_participation_id',
        'status',
        'receipt_reference',
        'package_reference',
        'received_at',
        'received_by',
        'opened_at',
        'opened_by',
        'bid_amount',
        'currency',
        'eligibility_documents_present',
        'technical_documents_present',
        'financial_documents_present',
        'bid_security_present',
        'opening_remarks',
    ];

    protected function casts(): array
    {
        return [
            'status' => BidSubmissionStatus::class,
            'received_at' => 'datetime',
            'opened_at' => 'datetime',
            'bid_amount' => 'decimal:2',
            'eligibility_documents_present' => 'boolean',
            'technical_documents_present' => 'boolean',
            'financial_documents_present' => 'boolean',
            'bid_security_present' => 'boolean',
        ];
    }

    /** @return BelongsTo<ProcurementRound, $this> */
    public function procurementRound(): BelongsTo
    {
        return $this->belongsTo(ProcurementRound::class);
    }

    /** @return BelongsTo<BidderParticipation, $this> */
    public function bidderParticipation(): BelongsTo
    {
        return $this->belongsTo(BidderParticipation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /** @return BelongsTo<User, $this> */
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }
}
