<?php

namespace App\Domain\Planning\Services;

use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\PlanningWorkflowAction;
use App\Domain\Planning\Models\AnnualProcurementPlan;
use App\Domain\Planning\Models\PlanningWorkflowEvent;
use App\Domain\Planning\Models\Ppmp;
use App\Domain\Procurement\Enums\ProcurementRole;
use App\Domain\Procurement\Models\ProcurementRoleAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PlanningWorkflowService
{
    /**
     * @return array<int, string>
     */
    public function availableActions(User $actor, Ppmp|AnnualProcurementPlan $subject): array
    {
        if ($actor->organization_id === null || $actor->organization_id !== $subject->organization_id) {
            return [];
        }

        $status = $this->status($subject);

        if ($subject instanceof Ppmp) {
            return $this->availablePpmpActions($actor, $subject, $status);
        }

        return $this->availableAppActions($actor, $status);
    }

    public function transition(
        User $actor,
        Ppmp|AnnualProcurementPlan $subject,
        PlanningWorkflowAction $action,
        ?string $remarks = null,
    ): void {
        abort_unless(
            $actor->organization_id !== null && $actor->organization_id === $subject->organization_id,
            404,
        );

        DB::transaction(function () use ($actor, $subject, $action, $remarks): void {
            $subject->refresh();
            $fromStatus = $this->status($subject);

            $toStatus = $subject instanceof Ppmp
                ? $this->transitionPpmp($actor, $subject, $action, $fromStatus)
                : $this->transitionApp($actor, $subject, $action, $fromStatus);

            $subject->status = $toStatus;
            $subject->save();

            PlanningWorkflowEvent::query()->create([
                'organization_id' => $subject->organization_id,
                'subject_type' => $subject instanceof Ppmp ? 'ppmp' : 'annual_procurement_plan',
                'subject_id' => $subject->id,
                'action' => $action->value,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'actor_id' => $actor->id,
                'remarks' => $remarks,
            ]);
        });
    }

    /**
     * @return array<int, string>
     */
    private function availablePpmpActions(
        User $actor,
        Ppmp $ppmp,
        PlanningStatus $status,
    ): array {
        if (in_array($status, [PlanningStatus::Draft, PlanningStatus::Returned], true)) {
            return $this->hasRole(
                $actor,
                ProcurementRole::EndUserHead,
                $ppmp->organizational_unit_id,
            ) ? [PlanningWorkflowAction::Submitted->value] : [];
        }

        if (! $this->hasRole($actor, ProcurementRole::PlanningReviewer)) {
            return [];
        }

        return match ($status) {
            PlanningStatus::Submitted => [
                PlanningWorkflowAction::ReviewStarted->value,
                PlanningWorkflowAction::Returned->value,
            ],
            PlanningStatus::UnderReview => [
                PlanningWorkflowAction::Approved->value,
                PlanningWorkflowAction::Returned->value,
            ],
            default => [],
        };
    }

    /**
     * @return array<int, string>
     */
    private function availableAppActions(User $actor, PlanningStatus $status): array
    {
        return match ($status) {
            PlanningStatus::Draft,
            PlanningStatus::Returned => $this->hasRole($actor, ProcurementRole::BacSecretariat)
                ? [PlanningWorkflowAction::Submitted->value]
                : [],
            PlanningStatus::Submitted => $this->hasRole($actor, ProcurementRole::BacChairperson)
                ? [
                    PlanningWorkflowAction::Recommended->value,
                    PlanningWorkflowAction::Returned->value,
                ]
                : [],
            PlanningStatus::Recommended => $this->hasRole($actor, ProcurementRole::Hope)
                ? [
                    PlanningWorkflowAction::Approved->value,
                    PlanningWorkflowAction::Returned->value,
                ]
                : [],
            default => [],
        };
    }

    private function transitionPpmp(
        User $actor,
        Ppmp $ppmp,
        PlanningWorkflowAction $action,
        PlanningStatus $fromStatus,
    ): PlanningStatus {
        if ($action === PlanningWorkflowAction::Submitted) {
            $this->requireStatus($fromStatus, [PlanningStatus::Draft, PlanningStatus::Returned]);
            $this->requireRole($actor, ProcurementRole::EndUserHead, $ppmp->organizational_unit_id);
            $ppmp->submitted_at = now();
            $ppmp->approved_by = null;
            $ppmp->approved_at = null;

            return PlanningStatus::Submitted;
        }

        $this->requireRole($actor, ProcurementRole::PlanningReviewer);

        if ($action === PlanningWorkflowAction::ReviewStarted) {
            $this->requireStatus($fromStatus, [PlanningStatus::Submitted]);

            return PlanningStatus::UnderReview;
        }

        if ($action === PlanningWorkflowAction::Approved) {
            $this->requireStatus($fromStatus, [PlanningStatus::UnderReview]);
            $ppmp->approved_by = $actor->id;
            $ppmp->approved_at = now();

            return PlanningStatus::Approved;
        }

        if ($action === PlanningWorkflowAction::Returned) {
            $this->requireStatus($fromStatus, [
                PlanningStatus::Submitted,
                PlanningStatus::UnderReview,
            ]);

            return PlanningStatus::Returned;
        }

        abort(409, 'This action is not valid for a PPMP.');
    }

    private function transitionApp(
        User $actor,
        AnnualProcurementPlan $plan,
        PlanningWorkflowAction $action,
        PlanningStatus $fromStatus,
    ): PlanningStatus {
        if ($action === PlanningWorkflowAction::Submitted) {
            $this->requireStatus($fromStatus, [PlanningStatus::Draft, PlanningStatus::Returned]);
            $this->requireRole($actor, ProcurementRole::BacSecretariat);
            $plan->recommended_by = null;
            $plan->recommended_at = null;
            $plan->approved_by = null;
            $plan->approved_at = null;

            return PlanningStatus::Submitted;
        }

        if ($action === PlanningWorkflowAction::Recommended) {
            $this->requireStatus($fromStatus, [PlanningStatus::Submitted]);
            $this->requireRole($actor, ProcurementRole::BacChairperson);
            $plan->recommended_by = $actor->id;
            $plan->recommended_at = now();

            return PlanningStatus::Recommended;
        }

        if ($action === PlanningWorkflowAction::Approved) {
            $this->requireStatus($fromStatus, [PlanningStatus::Recommended]);
            $this->requireRole($actor, ProcurementRole::Hope);
            $plan->approved_by = $actor->id;
            $plan->approved_at = now();

            return PlanningStatus::Approved;
        }

        if ($action === PlanningWorkflowAction::Returned) {
            if ($fromStatus === PlanningStatus::Submitted) {
                $this->requireRole($actor, ProcurementRole::BacChairperson);

                return PlanningStatus::Returned;
            }

            if ($fromStatus === PlanningStatus::Recommended) {
                $this->requireRole($actor, ProcurementRole::Hope);

                return PlanningStatus::Returned;
            }
        }

        abort(409, 'This action is not valid for an Annual Procurement Plan.');
    }

    private function hasRole(
        User $actor,
        ProcurementRole $role,
        ?int $organizationalUnitId = null,
    ): bool {
        if ($actor->organization_id === null) {
            return false;
        }

        return ProcurementRoleAssignment::query()
            ->where('organization_id', $actor->organization_id)
            ->where('user_id', $actor->id)
            ->where('role', $role->value)
            ->where('is_active', true)
            ->when(
                $organizationalUnitId !== null,
                fn ($query) => $query->where('organizational_unit_id', $organizationalUnitId),
                fn ($query) => $query->whereNull('organizational_unit_id'),
            )
            ->exists();
    }

    private function requireRole(
        User $actor,
        ProcurementRole $role,
        ?int $organizationalUnitId = null,
    ): void {
        abort_unless(
            $this->hasRole($actor, $role, $organizationalUnitId),
            403,
            'You are not assigned the procurement role required for this action.',
        );
    }

    /**
     * @param  array<int, PlanningStatus>  $allowed
     */
    private function requireStatus(PlanningStatus $status, array $allowed): void
    {
        abort_unless(
            in_array($status, $allowed, true),
            409,
            'This workflow action is not valid for the current status.',
        );
    }

    private function status(Model $subject): PlanningStatus
    {
        $status = $subject->getRawOriginal('status');

        return PlanningStatus::from((string) $status);
    }
}
