<?php

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\AnnualProcurementPlanType;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\AnnualProcurementPlan;
use App\Domain\Planning\Models\Ppmp;
use App\Domain\Procurement\Enums\ProcurementRoundStatus;
use App\Domain\Procurement\Enums\ProcurementStatus;
use App\Domain\Procurement\Models\ProcurementMethod;
use App\Domain\Procurement\Models\ProcurementProject;
use App\Domain\Procurement\Models\ProcurementRound;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function procurementRoundContext(string $suffix): array
{
    $organization = Organization::query()->create([
        'code' => "ROUND-ORG-{$suffix}",
        'name' => "Round Organization {$suffix}",
        'short_name' => "ROUND {$suffix}",
        'entity_type' => 'government',
        'is_active' => true,
    ]);

    $unit = OrganizationalUnit::query()->create([
        'organization_id' => $organization->id,
        'code' => "ROUND-UNIT-{$suffix}",
        'name' => "Procurement Unit {$suffix}",
        'unit_type' => 'procurement',
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
        'email' => "round-{$suffix}@example.test",
    ]);

    $method = ProcurementMethod::query()->create([
        'code' => "ROUND-METHOD-{$suffix}",
        'name' => "Round Method {$suffix}",
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $ppmp = Ppmp::query()->create([
        'organization_id' => $organization->id,
        'fiscal_year_id' => $fiscalYear->id,
        'organizational_unit_id' => $unit->id,
        'reference_no' => "PPMP-ROUND-{$suffix}",
        'title' => "PPMP Round {$suffix}",
        'version' => 1,
        'status' => PlanningStatus::Approved->value,
        'prepared_by' => $user->id,
    ]);

    $ppmpItem = $ppmp->items()->create([
        'recommended_procurement_method_id' => $method->id,
        'item_no' => '1',
        'title' => "Requirement {$suffix}",
        'description' => 'Requirement for procurement round testing.',
        'procurement_category' => ProcurementCategory::Goods->value,
        'quantity' => 1,
        'unit' => 'lot',
        'estimated_unit_cost' => 1_000_000,
        'estimated_budget' => 1_000_000,
        'funding_source' => 'GAA',
        'target_quarter' => 2,
        'status' => PlanningStatus::Approved->value,
    ]);

    $plan = AnnualProcurementPlan::query()->create([
        'organization_id' => $organization->id,
        'fiscal_year_id' => $fiscalYear->id,
        'reference_no' => "APP-ROUND-{$suffix}",
        'app_type' => AnnualProcurementPlanType::Final->value,
        'version' => 1,
        'status' => PlanningStatus::Approved->value,
        'prepared_by' => $user->id,
        'approved_by' => $user->id,
        'approved_at' => now(),
    ]);

    $appItem = $plan->items()->create([
        'ppmp_item_id' => $ppmpItem->id,
        'procurement_method_id' => $method->id,
        'app_item_no' => '1',
        'title' => "Requirement {$suffix}",
        'description' => 'Approved procurement requirement.',
        'procurement_category' => ProcurementCategory::Goods->value,
        'is_early_procurement_activity' => false,
        'bid_evaluation_criteria' => 'LCRB',
        'estimated_budget' => 1_000_000,
        'funding_source' => 'GAA',
        'schedule_start' => '2027-04-01',
        'schedule_end' => '2027-06-01',
        'status' => 'planned',
    ]);

    $project = ProcurementProject::query()->create([
        'organization_id' => $organization->id,
        'fiscal_year_id' => $fiscalYear->id,
        'app_item_id' => $appItem->id,
        'procurement_method_id' => $method->id,
        'reference_no' => "PROC-ROUND-{$suffix}",
        'title' => "Procurement Round Project {$suffix}",
        'description' => 'Project for round testing.',
        'procurement_category' => ProcurementCategory::Goods->value,
        'approved_budget' => 1_000_000,
        'funding_source' => 'GAA',
        'status' => ProcurementStatus::Planned->value,
        'current_stage' => 'initiation',
        'created_by' => $user->id,
    ]);

    return compact('organization', 'unit', 'fiscalYear', 'user', 'method', 'project');
}

test('procurement round workspace is isolated to the users organization', function () {
    $contextA = procurementRoundContext('INDEX-A');
    $contextB = procurementRoundContext('INDEX-B');

    $this->actingAs($contextA['user'])
        ->get(route('procurement.projects.rounds.index', $contextA['project']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('procurement/rounds/index')
            ->where('project.reference_no', 'PROC-ROUND-INDEX-A')
            ->has('project.rounds', 0));

    $this->actingAs($contextA['user'])
        ->get(route('procurement.projects.rounds.index', $contextB['project']))
        ->assertNotFound();
});

test('user can start the first procurement round', function () {
    $context = procurementRoundContext('START');

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']))
        ->assertRedirect(route('procurement.projects.rounds.index', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();
    $context['project']->refresh();

    expect($round->round_no)->toBe(1)
        ->and($round->procurement_method_id)->toBe($context['method']->id)
        ->and($round->getRawOriginal('status'))->toBe(ProcurementRoundStatus::Draft->value)
        ->and($round->created_by)->toBe($context['user']->id)
        ->and($context['project']->getRawOriginal('status'))->toBe(ProcurementStatus::Preparation->value)
        ->and($context['project']->current_stage)->toBe('pre_procurement');
});

test('a project cannot have two live procurement rounds', function () {
    $context = procurementRoundContext('LIVE');

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']))
        ->assertRedirect();

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']))
        ->assertSessionHasErrors(['round']);

    expect(ProcurementRound::query()->where('procurement_project_id', $context['project']->id)->count())
        ->toBe(1);
});

test('a failed procurement round is retained when a rebid round starts', function () {
    $context = procurementRoundContext('REBID');

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $firstRound = ProcurementRound::query()->firstOrFail();
    $firstRound->update([
        'status' => ProcurementRoundStatus::Failed->value,
        'failure_reason' => 'No bids were received.',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']))
        ->assertRedirect();

    $rounds = ProcurementRound::query()
        ->where('procurement_project_id', $context['project']->id)
        ->orderBy('round_no')
        ->get();

    expect($rounds)->toHaveCount(2)
        ->and($rounds[0]->round_no)->toBe(1)
        ->and($rounds[0]->getRawOriginal('status'))->toBe(ProcurementRoundStatus::Failed->value)
        ->and($rounds[0]->failure_reason)->toBe('No bids were received.')
        ->and($rounds[1]->round_no)->toBe(2)
        ->and($rounds[1]->getRawOriginal('status'))->toBe(ProcurementRoundStatus::Draft->value);
});

test('completed procurement projects cannot start another round', function () {
    $context = procurementRoundContext('LOCKED');
    $context['project']->update(['status' => ProcurementStatus::Completed->value]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']))
        ->assertSessionHasErrors(['round']);

    $this->assertDatabaseMissing('procurement_rounds', [
        'procurement_project_id' => $context['project']->id,
    ]);
});
