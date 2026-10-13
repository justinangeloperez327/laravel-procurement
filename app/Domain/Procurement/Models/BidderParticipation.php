<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Procurement\Enums\BidderParticipationStatus;
use App\Domain\Suppliers\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BidderParticipation extends Model
{
    protected $fillable = [
        'procurement_round_id',
        'supplier_id',
        'status',
        'supplier_code_snapshot',
        'supplier_name_snapshot',
        'philgeps_registration_no_snapshot',
        'representative_name',
        'representative_email',
        'representative_phone',
        'registered_at',
        'withdrawn_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BidderParticipationStatus::class,
            'registered_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProcurementRound, $this> */
    public function procurementRound(): BelongsTo
    {
        return $this->belongsTo(ProcurementRound::class);
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasOne<BidSubmission, $this> */
    public function bidSubmission(): HasOne
    {
        return $this->hasOne(BidSubmission::class);
    }
}
