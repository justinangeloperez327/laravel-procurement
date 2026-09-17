<?php

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\MarketScoping;
use App\Domain\Planning\Models\Ppmp;
use App\Domain\Procurement\Models\ProcurementMethod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function ppmpContext(string $suffix): array
{
    $organization = Organization::query()->create([
        'code' => "PPMP-ORG-{$suffix}",
        'name' => "PPMP Organization {$suffix}",
        'short_name' => "PPMP {$suffix}",
        'entity_type' => 'government',
        'is_active' => true,
    ]);

    $unit = OrganizationalUnit::query()->create([
        'organization_id' => $organization->id,
        'code' => "UNIT-{$suffix}",
        'name' => "Planning Unit {$suffix}",
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
        'email' => "ppmp-{$suffix}@example.test",
    ]);

    $marketScoping = MarketScoping::query()->create([
        'organization_id' => $organization->id,
        'fiscal_year_id' => $fiscalYear->id,
        'organizational_unit_id' => $unit->id,
        'reference_no' => "MS-PPMP-{$suffix}",
        'title' => "Market Scoping {$suffix}",
        'procurement_category' => ProcurementCategory::Goods->value,
        'status' => PlanningStatus::Draft->value,
        'prepared_by' => $user->id,
    ]);

    return compact('organization', 'unit', 'fiscalYear', 'user', 'marketScoping');
}

function ppmpMethod(string $suffix, bool $active = true): ProcurementMethod
{
    return ProcurementMethod::query()->create([
        'code' => "METHOD-{$suffix}",
        'name' => "Procurement Method {$suffix}",
        'is_active' => $active,
        'sort_order' => 1,
    ]);
}

function ppmpRecord(array $context, string $reference, string $status = 'draft'): Ppmp
{
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
        'market_scoping_id' => $context['marketScoping']->id,
        'item_no' => '1',
        'title' => 'Initial requirement',
        'procurement_category' => ProcurementCategory::Goods->value,
        'quantity' => 2,
        'unit' => 'unit',
        'estimated_unit_cost' => 100,
        'estimated_budget' => 200,
        'target_quarter' => 1,
        'status' => PlanningStatus::Draft->value,
    ]);

    return $ppmp;
}

test('PPMP index is isolated to the users organization', function () {
    $contextA = ppmpContext('INDEX-A');
    $contextB = ppmpContext('INDEX-B');

    ppmpRecord($contextA, 'PPMP-A-001');
    ppmpRecord($contextB, 'PPMP-B-001');

    $this->actingAs($contextA['user'])
        ->get(route('planning.ppmps.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('planning/ppmps/index')
            ->has('ppmps.data', 1)
            ->where('ppmps.data.0.reference_no', 'PPMP-A-001')
            ->where('ppmps.data.0.items_count', 1));
});

test('user can create a PPMP draft with line items', function () {
    $context = ppmpContext('CREATE');
    $method = ppmpMethod('CREATE');

    $this->actingAs($context['user'])
        ->post(route('planning.ppmps.store'), [
            'reference_no' => 'PPMP-2027-001',
            'fiscal_year_id' => $context['fiscalYear']->id,
            'organizational_unit_id' => $context['unit']->id,
            'title' => 'FY 2027 ICT Procurement Plan',
            'items' => [
                [
                    'item_no' => '1',
                    'title' => 'Workstations',
                    'description' => 'Business workstations for operational units.',
                    'procurement_category' => ProcurementCategory::Goods->value,
                    'quantity' => 10,
                    'unit' => 'unit',
                    'estimated_unit_cost' => 50000,
                    'estimated_budget' => 1,
                    'funding_source' => 'GAA',
                    'target_quarter' => 2,
                    'market_scoping_id' => $context['marketScoping']->id,
                    'recommended_procurement_method_id' => $method->id,
                ],
                [
                    'item_no' => '2',
                    'title' => 'Technical advisory services',
                    'procurement_category' => ProcurementCategory::ConsultingServices->value,
                    'quantity' => 1,
                    'unit' => 'lot',
                    'estimated_unit_cost' => null,
                    'estimated_budget' => 250000,
                    'funding_source' => 'GAA',
                    'target_quarter' => 3,
                ],
            ],
        ])
        ->assertRedirect(route('planning.ppmps.index'));

    $ppmp = Ppmp::query()->where('reference_no', 'PPMP-2027-001')->firstOrFail();

    expect($ppmp->version)->toBe(1)
        ->and($ppmp->getRawOriginal('status'))->toBe(PlanningStatus::Draft->value)
        ->and($ppmp->prepared_by)->toBe($context['user']->id)
        ->and($ppmp->items()->count())->toBe(2);

    $this->assertDatabaseHas('ppmp_items', [
        'ppmp_id' => $ppmp->id,
        'item_no' => '1',
        'estimated_budget' => 500000,
        'market_scoping_id' => $context['marketScoping']->id,
        'recommended_procurement_method_id' => $method->id,
    ]);

    $this->assertDatabaseHas('ppmp_items', [
        'ppmp_id' => $ppmp->id,
        'item_no' => '2',
        'estimated_budget' => 250000,
    ]);
});

test('PPMP rejects planning references from another organization', function () {
    $contextA = ppmpContext('VALIDATION-A');
    $contextB = ppmpContext('VALIDATION-B');

    $this->actingAs($contextA['user'])
        ->post(route('planning.ppmps.store'), [
            'reference_no' => 'PPMP-INVALID-001',
            'fiscal_year_id' => $contextA['fiscalYear']->id,
            'organizational_unit_id' => $contextA['unit']->id,
            'title' => 'Invalid PPMP',
            'items' => [[
                'item_no' => '1',
                'title' => 'Cross organization requirement',
                'procurement_category' => ProcurementCategory::Goods->value,
                'quantity' => 1,
                'unit' => 'lot',
                'estimated_budget' => 100000,
                'market_scoping_id' => $contextB['marketScoping']->id,
            ]],
        ])
        ->assertSessionHasErrors(['items.0.market_scoping_id']);

    $this->assertDatabaseMissing('ppmps', [
        'reference_no' => 'PPMP-INVALID-001',
    ]);
});

test('PPMP rejects inactive procurement methods', function () {
    $context = ppmpContext('METHOD');
    $inactiveMethod = ppmpMethod('INACTIVE', false);

    $this->actingAs($context['user'])
        ->post(route('planning.ppmps.store'), [
            'reference_no' => 'PPMP-METHOD-001',
            'fiscal_year_id' => $context['fiscalYear']->id,
            'organizational_unit_id' => $context['unit']->id,
            'title' => 'Method validation PPMP',
            'items' => [[
                'item_no' => '1',
                'title' => 'Requirement',
                'procurement_category' => ProcurementCategory::Goods->value,
                'quantity' => 1,
                'unit' => 'lot',
                'estimated_budget' => 100000,
                'recommended_procurement_method_id' => $inactiveMethod->id,
            ]],
        ])
        ->assertSessionHasErrors([
            'items.0.recommended_procurement_method_id',
        ]);
});

test('user can update a PPMP draft and replace its line items', function () {
    $context = ppmpContext('UPDATE');
    $ppmp = ppmpRecord($context, 'PPMP-UPDATE-001');

    $this->actingAs($context['user'])
        ->put(route('planning.ppmps.update', $ppmp), [
            'reference_no' => 'PPMP-UPDATE-001',
            'fiscal_year_id' => $context['fiscalYear']->id,
            'organizational_unit_id' => $context['unit']->id,
            'title' => 'Updated PPMP title',
            'items' => [[
                'item_no' => '10',
                'title' => 'Replacement requirement',
                'procurement_category' => ProcurementCategory::Infrastructure->value,
                'quantity' => 2.5,
                'unit' => 'lot',
                'estimated_unit_cost' => 100000,
                'estimated_budget' => 1,
                'funding_source' => 'Corporate Fund',
                'target_quarter' => 4,
            ]],
        ])
        ->assertRedirect(route('planning.ppmps.index'));

    $this->assertDatabaseHas('ppmps', [
        'id' => $ppmp->id,
        'title' => 'Updated PPMP title',
        'version' => 1,
        'status' => PlanningStatus::Draft->value,
    ]);

    $this->assertDatabaseMissing('ppmp_items', [
        'ppmp_id' => $ppmp->id,
        'item_no' => '1',
    ]);

    $this->assertDatabaseHas('ppmp_items', [
        'ppmp_id' => $ppmp->id,
        'item_no' => '10',
        'estimated_budget' => 250000,
    ]);
});

test('user cannot edit another organizations PPMP', function () {
    $contextA = ppmpContext('ISOLATION-A');
    $contextB = ppmpContext('ISOLATION-B');
    $foreignPpmp = ppmpRecord($contextB, 'PPMP-FOREIGN-001');

    $this->actingAs($contextA['user'])
        ->get(route('planning.ppmps.edit', $foreignPpmp))
        ->assertNotFound();
});

test('non draft PPMPs are locked from editing', function () {
    $context = ppmpContext('LOCKED');
    $ppmp = ppmpRecord(
        $context,
        'PPMP-SUBMITTED-001',
        PlanningStatus::Submitted->value,
    );

    $this->actingAs($context['user'])
        ->get(route('planning.ppmps.edit', $ppmp))
        ->assertStatus(409);
});
