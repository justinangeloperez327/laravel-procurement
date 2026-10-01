<?php

namespace App\Domain\Planning\Models;

use App\Domain\Organization\Models\Organization;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\PlanningWorkflowAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanningWorkflowEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'organization_id',
        'subject_type',
        'subject_id',
        'action',
        'from_status',
        'to_status',
        'actor_id',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'action' => PlanningWorkflowAction::class,
            'from_status' => PlanningStatus::class,
            'to_status' => PlanningStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
