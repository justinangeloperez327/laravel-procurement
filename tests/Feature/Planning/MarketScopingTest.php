<?php

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\MarketScoping;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function marketScopingContext(string $suffix): array
{
    $organization = Organization::query()->create([
        'code' => "ORG-{$suffix}",
        'name' => "Organization {$suffix}",
        'short_name' => "ORG {$suffix}",
        'entity_type' => 'government',
        'is_active' => true,
    ]);

    $unit = OrganizationalUnit::query()->create([
        'organization_id' => $organization->id,
        'code' => "BAC-{$suffix}",
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
        'email' => "procurement-{$suffix}@example.test",
    ]);

    return compact('organization', 'unit', 'fiscalYear', 'user');
}

function marketScopingRecord(array $context, string $reference, string $status = 'draft'): MarketScoping
{
    return MarketScoping::query()->create([
        'organization_id' => $context['organization']->id,
        'fiscal_year_id' => $context['fiscalYear']->id,
        'organizational_unit_id' => $context['unit']->id,
        'reference_no' => $reference,
        'title' => "Requirement {$reference}",
        'procurement_category' => ProcurementCategory::Goods->value,
        'description' => 'Initial requirement description.',
        'market_findings' => 'Initial market findings.',
        'recommended_strategy' => 'Initial procurement strategy.',
        'status' => $status,
        'prepared_by' => $context['user']->id,
    ]);
}

test('guests cannot access market scoping', function () {
    $this->get(route('planning.market-scopings.index'))
        ->assertRedirect(route('login'));
});

test('users without an organization cannot access market scoping', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('planning.market-scopings.index'))
        ->assertForbidden();
});

test('index only returns market scoping records for the users organization', function () {
    $contextA = marketScopingContext('A');
    $contextB = marketScopingContext('B');

    marketScopingRecord($contextA, 'MS-A-001');
    marketScopingRecord($contextB, 'MS-B-001');

    $this->actingAs($contextA['user'])
        ->get(route('planning.market-scopings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('planning/market-scopings/index')
            ->has('marketScopings.data', 1)
            ->where('marketScopings.data.0.reference_no', 'MS-A-001'));
});

test('user can create a market scoping draft', function () {
    $context = marketScopingContext('CREATE');

    $response = $this->actingAs($context['user'])->post(
        route('planning.market-scopings.store'),
        [
            'reference_no' => 'MS-2027-001',
            'fiscal_year_id' => $context['fiscalYear']->id,
            'organizational_unit_id' => $context['unit']->id,
            'title' => 'Supply of emergency communication equipment',
            'procurement_category' => ProcurementCategory::Goods->value,
            'description' => 'Planned procurement requirement.',
            'market_findings' => 'Multiple qualified suppliers are available.',
            'recommended_strategy' => 'Use competitive procurement.',
        ],
    );

    $response->assertRedirect(route('planning.market-scopings.index'));

    $this->assertDatabaseHas('market_scopings', [
        'organization_id' => $context['organization']->id,
        'reference_no' => 'MS-2027-001',
        'status' => PlanningStatus::Draft->value,
        'prepared_by' => $context['user']->id,
    ]);
});

test('market scoping cannot use fiscal years or units from another organization', function () {
    $contextA = marketScopingContext('VALIDATION-A');
    $contextB = marketScopingContext('VALIDATION-B');

    $this->actingAs($contextA['user'])
        ->post(route('planning.market-scopings.store'), [
            'reference_no' => 'MS-INVALID-001',
            'fiscal_year_id' => $contextB['fiscalYear']->id,
            'organizational_unit_id' => $contextB['unit']->id,
            'title' => 'Invalid cross-organization requirement',
            'procurement_category' => ProcurementCategory::Goods->value,
        ])
        ->assertSessionHasErrors([
            'fiscal_year_id',
            'organizational_unit_id',
        ]);

    $this->assertDatabaseMissing('market_scopings', [
        'reference_no' => 'MS-INVALID-001',
    ]);
});

test('user can update a draft market scoping', function () {
    $context = marketScopingContext('UPDATE');
    $marketScoping = marketScopingRecord($context, 'MS-UPDATE-001');

    $this->actingAs($context['user'])
        ->put(route('planning.market-scopings.update', $marketScoping), [
            'reference_no' => 'MS-UPDATE-001',
            'fiscal_year_id' => $context['fiscalYear']->id,
            'organizational_unit_id' => $context['unit']->id,
            'title' => 'Updated market scoping title',
            'procurement_category' => ProcurementCategory::Infrastructure->value,
            'description' => 'Updated description.',
            'market_findings' => 'Updated findings.',
            'recommended_strategy' => 'Updated strategy.',
        ])
        ->assertRedirect(route('planning.market-scopings.index'));

    $this->assertDatabaseHas('market_scopings', [
        'id' => $marketScoping->id,
        'title' => 'Updated market scoping title',
        'procurement_category' => ProcurementCategory::Infrastructure->value,
        'status' => PlanningStatus::Draft->value,
        'prepared_by' => $context['user']->id,
    ]);
});

test('user cannot edit another organizations market scoping', function () {
    $contextA = marketScopingContext('ISOLATION-A');
    $contextB = marketScopingContext('ISOLATION-B');
    $foreignRecord = marketScopingRecord($contextB, 'MS-FOREIGN-001');

    $this->actingAs($contextA['user'])
        ->get(route('planning.market-scopings.edit', $foreignRecord))
        ->assertNotFound();
});

test('non draft market scoping records are locked from editing', function () {
    $context = marketScopingContext('LOCKED');
    $marketScoping = marketScopingRecord(
        $context,
        'MS-SUBMITTED-001',
        PlanningStatus::Submitted->value,
    );

    $this->actingAs($context['user'])
        ->get(route('planning.market-scopings.edit', $marketScoping))
        ->assertStatus(409);
});
