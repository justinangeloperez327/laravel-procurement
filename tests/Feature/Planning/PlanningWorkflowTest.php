<?php

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Models\AnnualProcurementPlan;
use App\Domain\Planning\Models\Ppmp;
use App\Domain\Procurement\Enums\ProcurementRole;
use App\Domain\Procurement\Models\ProcurementRoleAssignment;
use App\Models\User;

function planningWorkflowContext(string $suffix): array
{
    $organization = Organization::query()->create([
        'code' => "WF-ORG-{$suffix}",
        'name' => "Workflow Organization {$suffix}",
        'short_name' => "WF {$suffix}",
        'entity_type' => 'government',
        'is_active' => true,
    ]);

    $unit = OrganizationalUnit::query()->create([
        'organization_id' => $organization->id,
        'code' => "WF-UNIT-{$suffix}",
        'name' => "Workflow Unit {$suffix}",
        'unit_type' => 'end_user',
        'is_active' => true,
    ]);

    $fiscalYear = FiscalYear::query()->create([
        'organization_id' => $organization->id,
        'year' => 2027,
        'starts_on' => '2027-01-01',
        'ends_on' => '2027-12-31',
        'status' => 'open',
    ]);

    $user = User::factory()->create([
        'organization_id' => $organization->id,
        'organizational_unit_id' => $unit->id,
        'email' => "workflow-{$suffix}@example.test",
    ]);

    return compact('organization', 'unit', 'fiscalYear', 'user');
}

function planningRole(array $context, User $user, ProcurementRole $role, ?int $unitId = null): void
{
    ProcurementRoleAssignment::query()->create([
        'organization_id' => $context['organization']->id,
        'organizational_unit_id' => $unitId,
        'user_id' => $user->id,
        'role' => $role->value,
        'is_active' => true,
    ]);
}

function workflowPpmp(array $context, string $status = 'draft'): Ppmp
{
    return Ppmp::query()->create([
        'organization_id' => $context['organization']->id,
        'fiscal_year_id' => $context['fiscalYear']->id,
        'organizational_unit_id' => $context['unit']->id,
        'reference_no' => 'WF-PPMP-001',
        'title' => 'Workflow PPMP',
        'version' => 1,
        'status' => $status,
        'prepared_by' => $context['user']->id,
    ]);
}

function workflowApp(array $context, string $status = 'draft'): AnnualProcurementPlan
{
    return AnnualProcurementPlan::query()->create([
        'organization_id' => $context['organization']->id,
        'fiscal_year_id' => $context['fiscalYear']->id,
        'reference_no' => 'WF-APP-001',
        'app_type' => 'final',
        'version' => 1,
        'status' => $status,
        'prepared_by' => $context['user']->id,
    ]);
}

test('end user head can submit a PPMP for its assigned organizational unit', function () {
    $context = planningWorkflowContext('PPMP-SUBMIT');
    $ppmp = workflowPpmp($context);
    planningRole($context, $context['user'], ProcurementRole::EndUserHead, $context['unit']->id);

    $this->actingAs($context['user'])
        ->patch(route('planning.ppmps.submit', $ppmp))
        ->assertRedirect();

    $ppmp->refresh();

    expect($ppmp->getRawOriginal('status'))->toBe(PlanningStatus::Submitted->value)
        ->and($ppmp->submitted_at)->not->toBeNull();

    $this->assertDatabaseHas('planning_workflow_events', [
        'subject_type' => 'ppmp',
        'subject_id' => $ppmp->id,
        'action' => 'submitted',
        'from_status' => 'draft',
        'to_status' => 'submitted',
        'actor_id' => $context['user']->id,
    ]);
});

test('end user head assignment is scoped to the PPMP organizational unit', function () {
    $context = planningWorkflowContext('PPMP-SCOPE');
    $otherUnit = OrganizationalUnit::query()->create([
        'organization_id' => $context['organization']->id,
        'code' => 'WF-OTHER',
        'name' => 'Other Unit',
        'unit_type' => 'end_user',
        'is_active' => true,
    ]);
    $ppmp = workflowPpmp($context);
    planningRole($context, $context['user'], ProcurementRole::EndUserHead, $otherUnit->id);

    $this->actingAs($context['user'])
        ->patch(route('planning.ppmps.submit', $ppmp))
        ->assertForbidden();

    expect($ppmp->fresh()->getRawOriginal('status'))->toBe(PlanningStatus::Draft->value);
});

test('planning reviewer must review a PPMP before approving it', function () {
    $context = planningWorkflowContext('PPMP-REVIEW');
    $reviewer = User::factory()->create([
        'organization_id' => $context['organization']->id,
        'email' => 'planning-reviewer@example.test',
    ]);
    planningRole($context, $reviewer, ProcurementRole::PlanningReviewer);
    $ppmp = workflowPpmp($context, PlanningStatus::Submitted->value);

    $this->actingAs($reviewer)
        ->patch(route('planning.ppmps.approve', $ppmp))
        ->assertStatus(409);

    $this->actingAs($reviewer)
        ->patch(route('planning.ppmps.review', $ppmp))
        ->assertRedirect();

    $this->actingAs($reviewer)
        ->patch(route('planning.ppmps.approve', $ppmp))
        ->assertRedirect();

    $ppmp->refresh();

    expect($ppmp->getRawOriginal('status'))->toBe(PlanningStatus::Approved->value)
        ->and($ppmp->approved_by)->toBe($reviewer->id)
        ->and($ppmp->approved_at)->not->toBeNull();
});

test('returned PPMP requires remarks and can be edited again', function () {
    $context = planningWorkflowContext('PPMP-RETURN');
    $reviewer = User::factory()->create([
        'organization_id' => $context['organization']->id,
        'email' => 'return-reviewer@example.test',
    ]);
    planningRole($context, $reviewer, ProcurementRole::PlanningReviewer);
    $ppmp = workflowPpmp($context, PlanningStatus::Submitted->value);

    $this->actingAs($reviewer)
        ->patch(route('planning.ppmps.return', $ppmp))
        ->assertSessionHasErrors(['remarks']);

    $this->actingAs($reviewer)
        ->patch(route('planning.ppmps.return', $ppmp), [
            'remarks' => 'Revise the delivery schedule and supporting estimates.',
        ])
        ->assertRedirect();

    expect($ppmp->fresh()->getRawOriginal('status'))->toBe(PlanningStatus::Returned->value);

    $this->actingAs($context['user'])
        ->get(route('planning.ppmps.edit', $ppmp))
        ->assertOk();

    $this->assertDatabaseHas('planning_workflow_events', [
        'subject_type' => 'ppmp',
        'subject_id' => $ppmp->id,
        'action' => 'returned',
        'remarks' => 'Revise the delivery schedule and supporting estimates.',
    ]);
});

test('APP follows BAC Secretariat to BAC Chairperson to HoPE approval order', function () {
    $context = planningWorkflowContext('APP-FLOW');
    $secretariat = $context['user'];
    $chair = User::factory()->create([
        'organization_id' => $context['organization']->id,
        'email' => 'bac-chair@example.test',
    ]);
    $hope = User::factory()->create([
        'organization_id' => $context['organization']->id,
        'email' => 'hope@example.test',
    ]);

    planningRole($context, $secretariat, ProcurementRole::BacSecretariat);
    planningRole($context, $chair, ProcurementRole::BacChairperson);
    planningRole($context, $hope, ProcurementRole::Hope);

    $plan = workflowApp($context);

    $this->actingAs($secretariat)
        ->patch(route('planning.annual-procurement-plans.submit', $plan))
        ->assertRedirect();

    $this->actingAs($hope)
        ->patch(route('planning.annual-procurement-plans.approve', $plan))
        ->assertStatus(409);

    $this->actingAs($chair)
        ->patch(route('planning.annual-procurement-plans.recommend', $plan))
        ->assertRedirect();

    $plan->refresh();

    expect($plan->getRawOriginal('status'))->toBe(PlanningStatus::Recommended->value)
        ->and($plan->recommended_by)->toBe($chair->id)
        ->and($plan->recommended_at)->not->toBeNull();

    $this->actingAs($hope)
        ->patch(route('planning.annual-procurement-plans.approve', $plan))
        ->assertRedirect();

    $plan->refresh();

    expect($plan->getRawOriginal('status'))->toBe(PlanningStatus::Approved->value)
        ->and($plan->approved_by)->toBe($hope->id)
        ->and($plan->approved_at)->not->toBeNull();
});

test('APP return authority follows the current approval stage', function () {
    $context = planningWorkflowContext('APP-RETURN');
    $chair = User::factory()->create([
        'organization_id' => $context['organization']->id,
        'email' => 'return-chair@example.test',
    ]);
    $hope = User::factory()->create([
        'organization_id' => $context['organization']->id,
        'email' => 'return-hope@example.test',
    ]);
    planningRole($context, $chair, ProcurementRole::BacChairperson);
    planningRole($context, $hope, ProcurementRole::Hope);

    $plan = workflowApp($context, PlanningStatus::Submitted->value);

    $this->actingAs($hope)
        ->patch(route('planning.annual-procurement-plans.return', $plan), [
            'remarks' => 'This should not be allowed at the BAC stage.',
        ])
        ->assertForbidden();

    $this->actingAs($chair)
        ->patch(route('planning.annual-procurement-plans.return', $plan), [
            'remarks' => 'Revise the consolidated procurement schedule.',
        ])
        ->assertRedirect();

    expect($plan->fresh()->getRawOriginal('status'))->toBe(PlanningStatus::Returned->value);

    $this->actingAs($context['user'])
        ->get(route('planning.annual-procurement-plans.edit', $plan))
        ->assertOk();
});

test('planning workflow cannot be acted on across organizations', function () {
    $contextA = planningWorkflowContext('TENANT-A');
    $contextB = planningWorkflowContext('TENANT-B');
    $ppmp = workflowPpmp($contextA);
    planningRole($contextB, $contextB['user'], ProcurementRole::PlanningReviewer);

    $this->actingAs($contextB['user'])
        ->patch(route('planning.ppmps.review', $ppmp))
        ->assertNotFound();

    expect($ppmp->fresh()->getRawOriginal('status'))->toBe(PlanningStatus::Draft->value);
});
