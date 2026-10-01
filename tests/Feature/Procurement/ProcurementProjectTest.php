<?php

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\AnnualProcurementPlanType;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\AnnualProcurementPlan;
use App\Domain\Planning\Models\AppItem;
use App\Domain\Planning\Models\Ppmp;
use App\Domain\Procurement\Enums\ProcurementStatus;
use App\Domain\Procurement\Models\ProcurementMethod;
use App\Domain\Procurement\Models\ProcurementProject;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function procurementProjectContext(string $suffix): array
{
    $organization = Organization::query()->create([
        'code' => "PROC-ORG-{$suffix}",
        'name' => "Procurement Organization {$suffix}",
        'short_name' => "PROC {$suffix}",
        'entity_type' => 'government',
        'is_active' => true,
    ]);

    $unit = OrganizationalUnit::query()->create([
        'organization_id' => $organization->id,
        'code' => "PROC-UNIT-{$suffix}",
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
        'email' => "proc-project-{$suffix}@example.test",
    ]);

    $officer = User::factory()->create([
        'organization_id' => $organization->id,
        'organizational_unit_id' => $unit->id,
        'email' => "proc-officer-{$suffix}@example.test",
        'is_active' => true,
    ]);

    $method = ProcurementMethod::query()->create([
        'code' => "PROC-METHOD-{$suffix}",
        'name' => "Procurement Method {$suffix}",
        'is_active' => true,
        'sort_order' => 1,
    ]);

    return compact(
        'organization',
        'unit',
        'fiscalYear',
        'user',
        'officer',
        'method',
    );
}

function procurementProjectAppItem(
    array $context,
    string $reference,
    string $planType = 'final',
    string $planStatus = 'approved',
    bool $epa = false,
    float $budget = 1_000_000,
): AppItem {
    $ppmp = Ppmp::query()->create([
        'organization_id' => $context['organization']->id,
        'fiscal_year_id' => $context['fiscalYear']->id,
        'organizational_unit_id' => $context['unit']->id,
        'reference_no' => "PPMP-{$reference}",
        'title' => "PPMP {$reference}",
        'version' => 1,
        'status' => PlanningStatus::Approved->value,
        'prepared_by' => $context['user']->id,
    ]);

    $ppmpItem = $ppmp->items()->create([
        'recommended_procurement_method_id' => $context['method']->id,
        'item_no' => '1',
        'title' => "Requirement {$reference}",
        'description' => 'Approved procurement requirement.',
        'procurement_category' => ProcurementCategory::Goods->value,
        'quantity' => 1,
        'unit' => 'lot',
        'estimated_unit_cost' => $budget,
        'estimated_budget' => $budget,
        'funding_source' => 'GAA',
        'target_quarter' => 2,
        'status' => PlanningStatus::Approved->value,
    ]);

    $plan = AnnualProcurementPlan::query()->create([
        'organization_id' => $context['organization']->id,
        'fiscal_year_id' => $context['fiscalYear']->id,
        'reference_no' => $reference,
        'app_type' => $planType,
        'version' => 1,
        'status' => $planStatus,
        'prepared_by' => $context['user']->id,
        'approved_by' => $planStatus === PlanningStatus::Approved->value
            ? $context['user']->id
            : null,
        'approved_at' => $planStatus === PlanningStatus::Approved->value
            ? now()
            : null,
    ]);

    return $plan->items()->create([
        'ppmp_item_id' => $ppmpItem->id,
        'procurement_method_id' => $context['method']->id,
        'app_item_no' => '1',
        'title' => "Requirement {$reference}",
        'description' => 'Approved procurement requirement.',
        'procurement_category' => ProcurementCategory::Goods->value,
        'is_early_procurement_activity' => $epa,
        'bid_evaluation_criteria' => 'LCRB',
        'estimated_budget' => $budget,
        'funding_source' => 'GAA',
        'schedule_start' => '2027-04-01',
        'schedule_end' => '2027-06-01',
        'status' => 'planned',
    ]);
}

function procurementProjectPayload(
    AppItem $source,
    User $officer,
    string $reference = 'PROC-2027-001',
    float $budget = 500_000,
): array {
    return [
        'app_item_id' => $source->id,
        'reference_no' => $reference,
        'title' => $source->title,
        'description' => $source->description,
        'approved_budget' => $budget,
        'procurement_officer_id' => $officer->id,
        'target_start_date' => '2027-04-01',
        'target_completion_date' => '2027-06-30',
    ];
}

test('procurement project index is isolated to the users organization', function () {
    $contextA = procurementProjectContext('INDEX-A');
    $contextB = procurementProjectContext('INDEX-B');
    $sourceA = procurementProjectAppItem($contextA, 'APP-PROC-A');
    $sourceB = procurementProjectAppItem($contextB, 'APP-PROC-B');

    ProcurementProject::query()->create([
        'organization_id' => $contextA['organization']->id,
        'fiscal_year_id' => $contextA['fiscalYear']->id,
        'app_item_id' => $sourceA->id,
        'procurement_method_id' => $contextA['method']->id,
        'reference_no' => 'PROJECT-A',
        'title' => 'Project A',
        'procurement_category' => ProcurementCategory::Goods->value,
        'approved_budget' => 100000,
        'funding_source' => 'GAA',
        'status' => ProcurementStatus::Planned->value,
        'created_by' => $contextA['user']->id,
    ]);

    ProcurementProject::query()->create([
        'organization_id' => $contextB['organization']->id,
        'fiscal_year_id' => $contextB['fiscalYear']->id,
        'app_item_id' => $sourceB->id,
        'procurement_method_id' => $contextB['method']->id,
        'reference_no' => 'PROJECT-B',
        'title' => 'Project B',
        'procurement_category' => ProcurementCategory::Goods->value,
        'approved_budget' => 100000,
        'funding_source' => 'GAA',
        'status' => ProcurementStatus::Planned->value,
        'created_by' => $contextB['user']->id,
    ]);

    $this->actingAs($contextA['user'])
        ->get(route('procurement.projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('procurement/projects/index')
            ->has('projects.data', 1)
            ->where('projects.data.0.reference_no', 'PROJECT-A'));
});

test('user can initiate a procurement project from an approved Final APP item', function () {
    $context = procurementProjectContext('CREATE');
    $source = procurementProjectAppItem(
        $context,
        'APP-FINAL-CREATE',
        AnnualProcurementPlanType::Final->value,
    );

    $this->actingAs($context['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload($source, $context['officer']),
        )
        ->assertRedirect(route('procurement.projects.index'));

    $project = ProcurementProject::query()
        ->where('reference_no', 'PROC-2027-001')
        ->firstOrFail();

    expect($project->app_item_id)->toBe($source->id)
        ->and($project->fiscal_year_id)->toBe($context['fiscalYear']->id)
        ->and($project->procurement_method_id)->toBe($context['method']->id)
        ->and($project->getRawOriginal('procurement_category'))->toBe(ProcurementCategory::Goods->value)
        ->and($project->getRawOriginal('status'))->toBe(ProcurementStatus::Planned->value)
        ->and($project->current_stage)->toBe('initiation')
        ->and($project->funding_source)->toBe('GAA')
        ->and($project->created_by)->toBe($context['user']->id);
});

test('unapproved APP items cannot initiate procurement', function () {
    $context = procurementProjectContext('UNAPPROVED');
    $source = procurementProjectAppItem(
        $context,
        'APP-UNAPPROVED',
        AnnualProcurementPlanType::Final->value,
        PlanningStatus::Recommended->value,
    );

    $this->actingAs($context['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload($source, $context['officer'], 'PROC-UNAPPROVED'),
        )
        ->assertSessionHasErrors(['app_item_id']);

    $this->assertDatabaseMissing('procurement_projects', [
        'reference_no' => 'PROC-UNAPPROVED',
    ]);
});

test('approved Indicative APP item must be marked for EPA to initiate procurement', function () {
    $context = procurementProjectContext('INDICATIVE-NO-EPA');
    $source = procurementProjectAppItem(
        $context,
        'APP-INDICATIVE-NO-EPA',
        AnnualProcurementPlanType::Indicative->value,
        PlanningStatus::Approved->value,
        false,
    );

    $this->actingAs($context['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload($source, $context['officer'], 'PROC-NO-EPA'),
        )
        ->assertSessionHasErrors(['app_item_id']);
});

test('approved Indicative APP item marked for EPA can initiate procurement', function () {
    $context = procurementProjectContext('INDICATIVE-EPA');
    $source = procurementProjectAppItem(
        $context,
        'APP-INDICATIVE-EPA',
        AnnualProcurementPlanType::Indicative->value,
        PlanningStatus::Approved->value,
        true,
    );

    $this->actingAs($context['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload($source, $context['officer'], 'PROC-EPA'),
        )
        ->assertRedirect(route('procurement.projects.index'));

    $this->assertDatabaseHas('procurement_projects', [
        'reference_no' => 'PROC-EPA',
        'app_item_id' => $source->id,
    ]);
});

test('APP items from another organization cannot initiate procurement', function () {
    $contextA = procurementProjectContext('SOURCE-A');
    $contextB = procurementProjectContext('SOURCE-B');
    $foreignSource = procurementProjectAppItem($contextB, 'APP-FOREIGN-SOURCE');

    $this->actingAs($contextA['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload(
                $foreignSource,
                $contextA['officer'],
                'PROC-FOREIGN-SOURCE',
            ),
        )
        ->assertNotFound();
});

test('one APP item can fund multiple projects without exceeding its approved budget', function () {
    $context = procurementProjectContext('SPLIT');
    $source = procurementProjectAppItem($context, 'APP-SPLIT', budget: 1_000_000);

    $this->actingAs($context['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload(
                $source,
                $context['officer'],
                'PROC-SPLIT-A',
                600_000,
            ),
        )
        ->assertRedirect(route('procurement.projects.index'));

    $this->actingAs($context['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload(
                $source,
                $context['officer'],
                'PROC-SPLIT-B',
                400_000,
            ),
        )
        ->assertRedirect(route('procurement.projects.index'));

    expect(ProcurementProject::query()->where('app_item_id', $source->id)->count())
        ->toBe(2);

    $this->actingAs($context['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload(
                $source,
                $context['officer'],
                'PROC-SPLIT-C',
                1,
            ),
        )
        ->assertSessionHasErrors(['approved_budget']);
});

test('procurement officer must belong to the same organization', function () {
    $contextA = procurementProjectContext('OFFICER-A');
    $contextB = procurementProjectContext('OFFICER-B');
    $source = procurementProjectAppItem($contextA, 'APP-OFFICER');

    $this->actingAs($contextA['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload(
                $source,
                $contextB['officer'],
                'PROC-FOREIGN-OFFICER',
            ),
        )
        ->assertSessionHasErrors(['procurement_officer_id']);
});

test('planned project can be updated without changing its APP source', function () {
    $context = procurementProjectContext('UPDATE');
    $source = procurementProjectAppItem($context, 'APP-UPDATE');

    $this->actingAs($context['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload($source, $context['officer'], 'PROC-UPDATE'),
        );

    $project = ProcurementProject::query()
        ->where('reference_no', 'PROC-UPDATE')
        ->firstOrFail();
    $originalSourceId = $project->app_item_id;

    $this->actingAs($context['user'])
        ->put(route('procurement.projects.update', $project), [
            'reference_no' => 'PROC-UPDATE-REV',
            'title' => 'Updated Procurement Project',
            'description' => 'Updated scope.',
            'approved_budget' => 750000,
            'procurement_officer_id' => $context['officer']->id,
            'target_start_date' => '2027-05-01',
            'target_completion_date' => '2027-07-31',
        ])
        ->assertRedirect(route('procurement.projects.index'));

    $project->refresh();

    expect($project->app_item_id)->toBe($originalSourceId)
        ->and($project->reference_no)->toBe('PROC-UPDATE-REV')
        ->and($project->approved_budget)->toBe('750000.00');
});

test('non planned procurement projects are locked from editing', function () {
    $context = procurementProjectContext('LOCKED');
    $source = procurementProjectAppItem($context, 'APP-LOCKED');

    $this->actingAs($context['user'])
        ->post(
            route('procurement.projects.store'),
            procurementProjectPayload($source, $context['officer'], 'PROC-LOCKED'),
        );

    $project = ProcurementProject::query()
        ->where('reference_no', 'PROC-LOCKED')
        ->firstOrFail();
    $project->update(['status' => ProcurementStatus::Preparation->value]);

    $this->actingAs($context['user'])
        ->get(route('procurement.projects.edit', $project))
        ->assertStatus(409);

    $this->actingAs($context['user'])
        ->put(route('procurement.projects.update', $project), [
            'reference_no' => 'PROC-LOCKED',
            'title' => 'Should Not Change',
            'description' => null,
            'approved_budget' => 500000,
            'procurement_officer_id' => $context['officer']->id,
            'target_start_date' => null,
            'target_completion_date' => null,
        ])
        ->assertStatus(409);
});
