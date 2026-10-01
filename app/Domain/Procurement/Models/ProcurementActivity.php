<?php

namespace App\Domain\Procurement\Models;

use App\Domain\Procurement\Enums\ProcurementActivityStatus;
use App\Domain\Procurement\Enums\ProcurementActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementActivity extends Model
{
    protected $fillable = [
        'procurement_round_id',
        'sequence_no',
        'activity_type',
        'status',
        'scheduled_at',
        'actual_at',
        'responsible_user_id',
        'minutes',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'activity_type' => ProcurementActivityType::class,
            'status' => ProcurementActivityStatus::class,
            'scheduled_at' => 'datetime',
            'actual_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProcurementRound, $this> */
    public function procurementRound(): BelongsTo
    {
        return $this->belongsTo(ProcurementRound::class);
    }

    /** @return BelongsTo<User, $this> */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
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
}
