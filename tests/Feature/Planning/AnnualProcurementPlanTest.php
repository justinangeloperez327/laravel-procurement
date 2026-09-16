<?php

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\AnnualProcurementPlanType;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\AnnualProcurementPlan;
use App\Domain\Planning\Models\Ppmp;
use App\Domain\Planning\Models\PpmpItem;
use App\Domain\Procurement\Models\ProcurementMethod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function appContext(string $suffix): array
{
    $organization = Organization::query()->create([
        'code' => "APP-ORG-{$suffix}",
        'name' => "APP Organization {$suffix}",
        'short_name' => "APP {$suffix}",
        'entity_type' => 'government',
        'is_active' => true,
    ]);

    $unit = OrganizationalUnit::query()->create([
        'organization_id' => $organization->id,
        'code' => "UNIT-{$suffix}",
        'name' => "End User Unit {$suffix}",
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
        'email' => "app-{$suffix}@example.test",
    ]);

    return compact('organization', 'unit', 'fiscalYear', 'user');
}

function appMethod(string $suffix, bool $active = true): ProcurementMethod
{
    return ProcurementMethod::query()->create([
        'code' => "APP-METHOD-{$suffix}",
        'name' => "APP Procurement Method {$suffix}",
        'is_active' => $active,
        'sort_order' => 1,
    ]);
}

function appPpmp(
    array $context,
    string $reference,
    string $status = 'approved',
    ?ProcurementMethod $method = null,
): Ppmp {
    $ppmp = Ppmp::query()->create([
        'organization_id' => $context['organization']->id,
        'fiscal_year_id' => $context['fiscalYear']->id,
        'organizational_unit_id' => $context['unit']->id,
        'reference_no' => $reference,
        'title' => "PPMP {$reference}",
        'version' => 1,
        'status' => $status,
        'prepared_by' => $context['user']->id,
    ]);

    $ppmp->items()->create([
        'recommended_procurement_method_id' => $method?->id,
        'item_no' => '1',
        'title' => "Requirement {$reference}",
        'description' => 'Requirement consolidated from the PPMP.',
        'procurement_category' => ProcurementCategory::Goods->value,
        'quantity' => 5,
        'unit' => 'unit',
        'estimated_unit_cost' => 100000,
        'estimated_budget' => 500000,
        'funding_source' => 'GAA',
        'target_quarter' => 2,
        'status' => PlanningStatus::Draft->value,
    ]);

    return $ppmp;
}

function appRecord(
    array $context,
    PpmpItem $source,
    ProcurementMethod $method,
    string $reference,
    string $status = 'draft',
): AnnualProcurementPlan {
    $plan = AnnualProcurementPlan::query()->create([
        'organization_id' => $context['organization']->id,
        'fiscal_year_id' => $context['fiscalYear']->id,
        'reference_no' => $reference,
        'app_type' => AnnualProcurementPlanType::Indicative->value,
        'version' => 1,
        'status' => $status,
        'prepared_by' => $context['user']->id,
    ]);

    $plan->items()->create([
        'ppmp_item_id' => $source->id,
        'procurement_method_id' => $method->id,
        'app_item_no' => '1',
        'title' => $source->title,
        'description' => $source->description,
        'procurement_category' => ProcurementCategory::Goods->value,
        'is_early_procurement_activity' => false,
        'bid_evaluation_criteria' => 'LCRB',
        'estimated_budget' => 500000,
        'funding_source' => 'GAA',
        'schedule_start' => '2027-04-01',
        'schedule_end' => '2027-06-01',
        'procurement_strategy_tools' => ['Framework Agreement'],
        'remarks' => 'Initial APP line.',
        'status' => 'planned',
    ]);

    return $plan;
}

function appPayload(
    array $context,
    PpmpItem $source,
    ProcurementMethod $method,
    string $reference = 'APP-2027-001',
    string $type = 'indicative',
): array {
    return [
        'reference_no' => $reference,
        'fiscal_year_id' => $context['fiscalYear']->id,
        'app_type' => $type,
        'items' => [[
            'ppmp_item_id' => $source->id,
            'procurement_method_id' => $method->id,
            'app_item_no' => '1',
            'title' => $source->title,
            'description' => $source->description,
            'procurement_category' => ProcurementCategory::Goods->value,
            'is_early_procurement_activity' => true,
            'bid_evaluation_criteria' => 'LCRB',
            'schedule_start' => '2027-04',
            'schedule_end' => '2027-06',
            'funding_source' => 'GAA',
            'estimated_budget' => 500000,
            'procurement_strategy_tools' => [
                'Framework Agreement',
                'Pooled Procurement',
            ],
            'remarks' => 'For consolidation.',
        ]],
    ];
}

test('Annual Procurement Plan index is isolated to the users organization', function () {
    $contextA = appContext('INDEX-A');
    $contextB = appContext('INDEX-B');
    $method = appMethod('INDEX');
    $sourceA = appPpmp($contextA, 'PPMP-APP-A')->items()->firstOrFail();
    $sourceB = appPpmp($contextB, 'PPMP-APP-B')->items()->firstOrFail();

    appRecord($contextA, $sourceA, $method, 'APP-A-001');
    appRecord($contextB, $sourceB, $method, 'APP-B-001');

    $this->actingAs($contextA['user'])
        ->get(route('planning.annual-procurement-plans.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('planning/annual-procurement-plans/index')
            ->has('plans.data', 1)
            ->where('plans.data.0.reference_no', 'APP-A-001')
            ->where('plans.data.0.items_count', 1));
});

test('user can create an Indicative APP from a submitted PPMP', function () {
    $context = appContext('CREATE');
    $method = appMethod('CREATE');
    $ppmp = appPpmp(
        $context,
        'PPMP-SUBMITTED-001',
        PlanningStatus::Submitted->value,
        $method,
    );
    $source = $ppmp->items()->firstOrFail();

    $this->actingAs($context['user'])
        ->post(
            route('planning.annual-procurement-plans.store'),
            appPayload($context, $source, $method),
        )
        ->assertRedirect(route('planning.annual-procurement-plans.index'));

    $plan = AnnualProcurementPlan::query()
        ->where('reference_no', 'APP-2027-001')
        ->firstOrFail();

    expect($plan->version)->toBe(1)
        ->and($plan->getRawOriginal('app_type'))->toBe(AnnualProcurementPlanType::Indicative->value)
        ->and($plan->getRawOriginal('status'))->toBe(PlanningStatus::Draft->value)
        ->and($plan->prepared_by)->toBe($context['user']->id)
        ->and($plan->items()->count())->toBe(1);

    $this->assertDatabaseHas('app_items', [
        'annual_procurement_plan_id' => $plan->id,
        'ppmp_item_id' => $source->id,
        'procurement_method_id' => $method->id,
        'is_early_procurement_activity' => 1,
        'bid_evaluation_criteria' => 'LCRB',
        'schedule_start' => '2027-04-01',
        'schedule_end' => '2027-06-01',
        'estimated_budget' => 500000,
        'status' => 'planned',
    ]);

    expect($plan->items()->firstOrFail()->procurement_strategy_tools)
        ->toBe(['Framework Agreement', 'Pooled Procurement']);
});

test('Final APP rejects a PPMP that is not approved', function () {
    $context = appContext('FINAL-STATUS');
    $method = appMethod('FINAL-STATUS');
    $source = appPpmp(
        $context,
        'PPMP-NOT-APPROVED',
        PlanningStatus::Submitted->value,
    )->items()->firstOrFail();

    $this->actingAs($context['user'])
        ->post(
            route('planning.annual-procurement-plans.store'),
            appPayload(
                $context,
                $source,
                $method,
                'APP-FINAL-INVALID',
                AnnualProcurementPlanType::Final->value,
            ),
        )
        ->assertSessionHasErrors(['items.0.ppmp_item_id']);

    $this->assertDatabaseMissing('annual_procurement_plans', [
        'reference_no' => 'APP-FINAL-INVALID',
    ]);
});

test('Final APP accepts an approved PPMP', function () {
    $context = appContext('FINAL-APPROVED');
    $method = appMethod('FINAL-APPROVED');
    $source = appPpmp(
        $context,
        'PPMP-APPROVED',
        PlanningStatus::Approved->value,
    )->items()->firstOrFail();

    $this->actingAs($context['user'])
        ->post(
            route('planning.annual-procurement-plans.store'),
            appPayload(
                $context,
                $source,
                $method,
                'APP-FINAL-001',
                AnnualProcurementPlanType::Final->value,
            ),
        )
        ->assertRedirect(route('planning.annual-procurement-plans.index'));

    $this->assertDatabaseHas('annual_procurement_plans', [
        'reference_no' => 'APP-FINAL-001',
        'app_type' => AnnualProcurementPlanType::Final->value,
        'status' => PlanningStatus::Draft->value,
    ]);
});

test('APP rejects PPMP items from another organization', function () {
    $contextA = appContext('SOURCE-A');
    $contextB = appContext('SOURCE-B');
    $method = appMethod('SOURCE');
    $foreignSource = appPpmp(
        $contextB,
        'PPMP-FOREIGN',
        PlanningStatus::Approved->value,
    )->items()->firstOrFail();

    $this->actingAs($contextA['user'])
        ->post(
            route('planning.annual-procurement-plans.store'),
            appPayload(
                $contextA,
                $foreignSource,
                $method,
                'APP-FOREIGN-001',
            ),
        )
        ->assertSessionHasErrors(['items.0.ppmp_item_id']);
});

test('APP rejects PPMP items from another fiscal year', function () {
    $context = appContext('FISCAL');
    $method = appMethod('FISCAL');
    $otherFiscalYear = FiscalYear::query()->create([
        'organization_id' => $context['organization']->id,
        'year' => 2028,
        'starts_on' => '2028-01-01',
        'ends_on' => '2028-12-31',
        'status' => 'open',
    ]);
    $otherContext = [...$context, 'fiscalYear' => $otherFiscalYear];
    $foreignSource = appPpmp(
        $otherContext,
        'PPMP-2028-001',
        PlanningStatus::Approved->value,
    )->items()->firstOrFail();

    $this->actingAs($context['user'])
        ->post(
            route('planning.annual-procurement-plans.store'),
            appPayload(
                $context,
                $foreignSource,
                $method,
                'APP-WRONG-FY',
            ),
        )
        ->assertSessionHasErrors(['items.0.ppmp_item_id']);
});

test('APP rejects inactive procurement methods', function () {
    $context = appContext('METHOD');
    $inactiveMethod = appMethod('INACTIVE', false);
    $source = appPpmp(
        $context,
        'PPMP-METHOD-001',
        PlanningStatus::Approved->value,
    )->items()->firstOrFail();

    $this->actingAs($context['user'])
        ->post(
            route('planning.annual-procurement-plans.store'),
            appPayload($context, $source, $inactiveMethod, 'APP-METHOD-001'),
        )
        ->assertSessionHasErrors(['items.0.procurement_method_id']);
});

test('APP rejects procurement schedules that end before they start', function () {
    $context = appContext('SCHEDULE');
    $method = appMethod('SCHEDULE');
    $source = appPpmp(
        $context,
        'PPMP-SCHEDULE-001',
        PlanningStatus::Approved->value,
    )->items()->firstOrFail();
    $payload = appPayload($context, $source, $method, 'APP-SCHEDULE-001');
    $payload['items'][0]['schedule_start'] = '2027-08';
    $payload['items'][0]['schedule_end'] = '2027-07';

    $this->actingAs($context['user'])
        ->post(route('planning.annual-procurement-plans.store'), $payload)
        ->assertSessionHasErrors(['items.0.schedule_end']);
});

test('user can update an APP draft and replace its consolidated lines', function () {
    $context = appContext('UPDATE');
    $method = appMethod('UPDATE');
    $firstSource = appPpmp(
        $context,
        'PPMP-UPDATE-A',
        PlanningStatus::Submitted->value,
    )->items()->firstOrFail();
    $secondSource = appPpmp(
        $context,
        'PPMP-UPDATE-B',
        PlanningStatus::Approved->value,
    )->items()->firstOrFail();
    $plan = appRecord($context, $firstSource, $method, 'APP-UPDATE-001');
    $payload = appPayload(
        $context,
        $secondSource,
        $method,
        'APP-UPDATE-001',
    );
    $payload['items'][0]['app_item_no'] = '10';
    $payload['items'][0]['estimated_budget'] = 750000;

    $this->actingAs($context['user'])
        ->put(
            route('planning.annual-procurement-plans.update', $plan),
            $payload,
        )
        ->assertRedirect(route('planning.annual-procurement-plans.index'));

    $this->assertDatabaseMissing('app_items', [
        'annual_procurement_plan_id' => $plan->id,
        'ppmp_item_id' => $firstSource->id,
    ]);

    $this->assertDatabaseHas('app_items', [
        'annual_procurement_plan_id' => $plan->id,
        'ppmp_item_id' => $secondSource->id,
        'app_item_no' => '10',
        'estimated_budget' => 750000,
    ]);
});

test('user cannot edit another organizations APP', function () {
    $contextA = appContext('ISOLATION-A');
    $contextB = appContext('ISOLATION-B');
    $method = appMethod('ISOLATION');
    $source = appPpmp(
        $contextB,
        'PPMP-APP-FOREIGN',
        PlanningStatus::Approved->value,
    )->items()->firstOrFail();
    $foreignPlan = appRecord(
        $contextB,
        $source,
        $method,
        'APP-FOREIGN-EDIT',
    );

    $this->actingAs($contextA['user'])
        ->get(route('planning.annual-procurement-plans.edit', $foreignPlan))
        ->assertNotFound();
});

test('non draft Annual Procurement Plans are locked from editing', function () {
    $context = appContext('LOCKED');
    $method = appMethod('LOCKED');
    $source = appPpmp(
        $context,
        'PPMP-APP-LOCKED',
        PlanningStatus::Approved->value,
    )->items()->firstOrFail();
    $plan = appRecord(
        $context,
        $source,
        $method,
        'APP-LOCKED-001',
        PlanningStatus::Submitted->value,
    );

    $this->actingAs($context['user'])
        ->get(route('planning.annual-procurement-plans.edit', $plan))
        ->assertStatus(409);
});
