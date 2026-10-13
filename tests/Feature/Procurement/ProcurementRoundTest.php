<?php

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\AnnualProcurementPlanType;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\AnnualProcurementPlan;
use App\Domain\Planning\Models\Ppmp;
use App\Domain\Procurement\Enums\BidderParticipationStatus;
use App\Domain\Procurement\Enums\BidSubmissionStatus;
use App\Domain\Procurement\Enums\ProcurementActivityStatus;
use App\Domain\Procurement\Enums\ProcurementActivityType;
use App\Domain\Procurement\Enums\ProcurementRoundStatus;
use App\Domain\Procurement\Enums\ProcurementStatus;
use App\Domain\Procurement\Models\BidderParticipation;
use App\Domain\Procurement\Models\BidSubmission;
use App\Domain\Procurement\Models\ProcurementActivity;
use App\Domain\Procurement\Models\ProcurementMethod;
use App\Domain\Procurement\Models\ProcurementProject;
use App\Domain\Procurement\Models\ProcurementRound;
use App\Domain\Suppliers\Models\Supplier;
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

test('procurement activity workspace is isolated to the users organization', function () {
    $contextA = procurementRoundContext('ACTIVITY-INDEX-A');
    $contextB = procurementRoundContext('ACTIVITY-INDEX-B');

    $this->actingAs($contextA['user'])
        ->post(route('procurement.projects.rounds.store', $contextA['project']));

    $this->actingAs($contextB['user'])
        ->post(route('procurement.projects.rounds.store', $contextB['project']));

    $roundA = ProcurementRound::query()
        ->where('procurement_project_id', $contextA['project']->id)
        ->firstOrFail();
    $roundB = ProcurementRound::query()
        ->where('procurement_project_id', $contextB['project']->id)
        ->firstOrFail();

    $this->actingAs($contextA['user'])
        ->get(route('procurement.projects.rounds.activities.index', [$contextA['project'], $roundA]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('procurement/activities/index')
            ->where('project.reference_no', 'PROC-ROUND-ACTIVITY-INDEX-A')
            ->where('round.round_no', 1)
            ->has('round.activities', 0));

    $this->actingAs($contextA['user'])
        ->get(route('procurement.projects.rounds.activities.index', [$contextB['project'], $roundB]))
        ->assertNotFound();
});

test('user can schedule procurement activities in sequence', function () {
    $context = procurementRoundContext('ACTIVITY-SCHEDULE');

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.activities.store', [$context['project'], $round]), [
            'activity_type' => ProcurementActivityType::PreProcurementConference->value,
            'scheduled_at' => '2027-03-15 09:00:00',
            'responsible_user_id' => $context['user']->id,
            'remarks' => 'Initial procurement conference.',
        ])
        ->assertRedirect(route('procurement.projects.rounds.activities.index', [$context['project'], $round]));

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.activities.store', [$context['project'], $round]), [
            'activity_type' => ProcurementActivityType::AdvertisementPosting->value,
            'scheduled_at' => '2027-03-16 08:00:00',
        ])
        ->assertRedirect();

    $activities = ProcurementActivity::query()
        ->where('procurement_round_id', $round->id)
        ->orderBy('sequence_no')
        ->get();

    expect($activities)->toHaveCount(2)
        ->and($activities[0]->sequence_no)->toBe(1)
        ->and($activities[0]->getRawOriginal('activity_type'))->toBe(ProcurementActivityType::PreProcurementConference->value)
        ->and($activities[0]->getRawOriginal('status'))->toBe(ProcurementActivityStatus::Scheduled->value)
        ->and($activities[0]->responsible_user_id)->toBe($context['user']->id)
        ->and($activities[0]->created_by)->toBe($context['user']->id)
        ->and($activities[1]->sequence_no)->toBe(2)
        ->and($activities[1]->getRawOriginal('activity_type'))->toBe(ProcurementActivityType::AdvertisementPosting->value);
});

test('activity responsible user must belong to the same organization', function () {
    $contextA = procurementRoundContext('ACTIVITY-USER-A');
    $contextB = procurementRoundContext('ACTIVITY-USER-B');

    $this->actingAs($contextA['user'])
        ->post(route('procurement.projects.rounds.store', $contextA['project']));

    $round = ProcurementRound::query()
        ->where('procurement_project_id', $contextA['project']->id)
        ->firstOrFail();

    $this->actingAs($contextA['user'])
        ->post(route('procurement.projects.rounds.activities.store', [$contextA['project'], $round]), [
            'activity_type' => ProcurementActivityType::PreBidConference->value,
            'responsible_user_id' => $contextB['user']->id,
        ])
        ->assertSessionHasErrors(['responsible_user_id']);

    $this->assertDatabaseMissing('procurement_activities', [
        'procurement_round_id' => $round->id,
    ]);
});

test('user can complete a procurement activity with actual execution evidence', function () {
    $context = procurementRoundContext('ACTIVITY-COMPLETE');

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.activities.store', [$context['project'], $round]), [
            'activity_type' => ProcurementActivityType::BidOpening->value,
            'scheduled_at' => '2027-04-20 10:00:00',
        ]);

    $activity = ProcurementActivity::query()->firstOrFail();

    $this->actingAs($context['user'])
        ->patch(route('procurement.projects.rounds.activities.complete', [$context['project'], $round, $activity]), [
            'actual_at' => '2027-04-20 10:15:00',
            'minutes' => 'Bid opening completed with the BAC and observers present.',
            'remarks' => 'Proceed to bid evaluation.',
        ])
        ->assertRedirect(route('procurement.projects.rounds.activities.index', [$context['project'], $round]));

    $activity->refresh();

    expect($activity->getRawOriginal('status'))->toBe(ProcurementActivityStatus::Completed->value)
        ->and($activity->actual_at?->format('Y-m-d H:i:s'))->toBe('2027-04-20 10:15:00')
        ->and($activity->minutes)->toBe('Bid opening completed with the BAC and observers present.')
        ->and($activity->remarks)->toBe('Proceed to bid evaluation.')
        ->and($activity->updated_by)->toBe($context['user']->id);
});

test('failed procurement rounds keep their activity history read only', function () {
    $context = procurementRoundContext('ACTIVITY-FAILED');

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $activity = ProcurementActivity::query()->create([
        'procurement_round_id' => $round->id,
        'sequence_no' => 1,
        'activity_type' => ProcurementActivityType::BidSubmissionDeadline->value,
        'status' => ProcurementActivityStatus::Scheduled->value,
        'scheduled_at' => '2027-04-01 12:00:00',
        'created_by' => $context['user']->id,
    ]);

    $round->update([
        'status' => ProcurementRoundStatus::Failed->value,
        'failure_reason' => 'No responsive bids.',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.activities.store', [$context['project'], $round]), [
            'activity_type' => ProcurementActivityType::BidOpening->value,
        ])
        ->assertSessionHasErrors(['activity']);

    $this->actingAs($context['user'])
        ->patch(route('procurement.projects.rounds.activities.complete', [$context['project'], $round, $activity]))
        ->assertSessionHasErrors(['activity']);

    expect(ProcurementActivity::query()->where('procurement_round_id', $round->id)->count())
        ->toBe(1)
        ->and($activity->fresh()?->getRawOriginal('status'))
        ->toBe(ProcurementActivityStatus::Scheduled->value);
});

test('bidder workspace is isolated to the users organization', function () {
    $contextA = procurementRoundContext('BIDDER-INDEX-A');
    $contextB = procurementRoundContext('BIDDER-INDEX-B');

    $this->actingAs($contextA['user'])
        ->post(route('procurement.projects.rounds.store', $contextA['project']));
    $this->actingAs($contextB['user'])
        ->post(route('procurement.projects.rounds.store', $contextB['project']));

    $roundA = ProcurementRound::query()
        ->where('procurement_project_id', $contextA['project']->id)
        ->firstOrFail();
    $roundB = ProcurementRound::query()
        ->where('procurement_project_id', $contextB['project']->id)
        ->firstOrFail();

    $this->actingAs($contextA['user'])
        ->get(route('procurement.projects.rounds.bidders.index', [$contextA['project'], $roundA]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('procurement/bidders/index')
            ->where('project.reference_no', 'PROC-ROUND-BIDDER-INDEX-A')
            ->where('round.round_no', 1)
            ->has('round.bidder_participations', 0));

    $this->actingAs($contextA['user'])
        ->get(route('procurement.projects.rounds.bidders.index', [$contextB['project'], $roundB]))
        ->assertNotFound();
});

test('user can register an active supplier as a bidder with an audit snapshot', function () {
    $context = procurementRoundContext('BIDDER-REGISTER');

    $supplier = Supplier::query()->create([
        'organization_id' => $context['organization']->id,
        'supplier_code' => 'SUP-BIDDER-REGISTER',
        'legal_name' => 'Registered Bidder Corporation',
        'philgeps_registration_no' => 'PHILGEPS-12345',
        'email' => 'bidder@example.test',
        'status' => 'active',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bidders.store', [$context['project'], $round]), [
            'supplier_id' => $supplier->id,
            'representative_name' => 'Maria Santos',
            'representative_email' => 'maria@example.test',
            'representative_phone' => '+63 900 000 0000',
        ])
        ->assertRedirect(route('procurement.projects.rounds.bidders.index', [$context['project'], $round]));

    $participation = BidderParticipation::query()->firstOrFail();

    expect($participation->supplier_id)->toBe($supplier->id)
        ->and($participation->getRawOriginal('status'))->toBe(BidderParticipationStatus::Registered->value)
        ->and($participation->supplier_code_snapshot)->toBe('SUP-BIDDER-REGISTER')
        ->and($participation->supplier_name_snapshot)->toBe('Registered Bidder Corporation')
        ->and($participation->philgeps_registration_no_snapshot)->toBe('PHILGEPS-12345')
        ->and($participation->representative_name)->toBe('Maria Santos')
        ->and($participation->created_by)->toBe($context['user']->id)
        ->and($participation->registered_at)->not->toBeNull();
});

test('supplier from another organization cannot register in the round', function () {
    $contextA = procurementRoundContext('BIDDER-ORG-A');
    $contextB = procurementRoundContext('BIDDER-ORG-B');

    $foreignSupplier = Supplier::query()->create([
        'organization_id' => $contextB['organization']->id,
        'supplier_code' => 'SUP-FOREIGN',
        'legal_name' => 'Foreign Organization Supplier',
        'status' => 'active',
    ]);

    $this->actingAs($contextA['user'])
        ->post(route('procurement.projects.rounds.store', $contextA['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $this->actingAs($contextA['user'])
        ->post(route('procurement.projects.rounds.bidders.store', [$contextA['project'], $round]), [
            'supplier_id' => $foreignSupplier->id,
        ])
        ->assertSessionHasErrors(['supplier_id']);

    $this->assertDatabaseMissing('bidder_participations', [
        'procurement_round_id' => $round->id,
        'supplier_id' => $foreignSupplier->id,
    ]);
});

test('supplier can participate only once in the same procurement round', function () {
    $context = procurementRoundContext('BIDDER-DUPLICATE');

    $supplier = Supplier::query()->create([
        'organization_id' => $context['organization']->id,
        'supplier_code' => 'SUP-DUPLICATE',
        'legal_name' => 'Duplicate Test Supplier',
        'status' => 'active',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $payload = ['supplier_id' => $supplier->id];

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bidders.store', [$context['project'], $round]), $payload)
        ->assertRedirect();

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bidders.store', [$context['project'], $round]), $payload)
        ->assertSessionHasErrors(['supplier_id']);

    expect(BidderParticipation::query()
        ->where('procurement_round_id', $round->id)
        ->where('supplier_id', $supplier->id)
        ->count())->toBe(1);
});

test('withdrawing a bidder preserves the participation record', function () {
    $context = procurementRoundContext('BIDDER-WITHDRAW');

    $supplier = Supplier::query()->create([
        'organization_id' => $context['organization']->id,
        'supplier_code' => 'SUP-WITHDRAW',
        'legal_name' => 'Withdraw Test Supplier',
        'status' => 'active',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bidders.store', [$context['project'], $round]), [
            'supplier_id' => $supplier->id,
        ]);

    $participation = BidderParticipation::query()->firstOrFail();

    $this->actingAs($context['user'])
        ->patch(route('procurement.projects.rounds.bidders.withdraw', [$context['project'], $round, $participation]))
        ->assertRedirect(route('procurement.projects.rounds.bidders.index', [$context['project'], $round]));

    $participation->refresh();

    expect($participation->getRawOriginal('status'))->toBe(BidderParticipationStatus::Withdrawn->value)
        ->and($participation->withdrawn_at)->not->toBeNull()
        ->and($participation->updated_by)->toBe($context['user']->id)
        ->and(BidderParticipation::query()->count())->toBe(1);
});

test('failed procurement rounds keep bidder participation read only', function () {
    $context = procurementRoundContext('BIDDER-FAILED');

    $supplier = Supplier::query()->create([
        'organization_id' => $context['organization']->id,
        'supplier_code' => 'SUP-FAILED',
        'legal_name' => 'Failed Round Supplier',
        'status' => 'active',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();
    $round->update([
        'status' => ProcurementRoundStatus::Failed->value,
        'failure_reason' => 'Round formally failed.',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bidders.store', [$context['project'], $round]), [
            'supplier_id' => $supplier->id,
        ])
        ->assertSessionHasErrors(['bidder']);

    $this->assertDatabaseMissing('bidder_participations', [
        'procurement_round_id' => $round->id,
    ]);
});

test('bid submission workspace is isolated to the users organization', function () {
    $contextA = procurementRoundContext('BID-INDEX-A');
    $contextB = procurementRoundContext('BID-INDEX-B');

    $this->actingAs($contextA['user'])
        ->post(route('procurement.projects.rounds.store', $contextA['project']));
    $this->actingAs($contextB['user'])
        ->post(route('procurement.projects.rounds.store', $contextB['project']));

    $roundA = ProcurementRound::query()
        ->where('procurement_project_id', $contextA['project']->id)
        ->firstOrFail();
    $roundB = ProcurementRound::query()
        ->where('procurement_project_id', $contextB['project']->id)
        ->firstOrFail();

    $this->actingAs($contextA['user'])
        ->get(route('procurement.projects.rounds.bids.index', [$contextA['project'], $roundA]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('procurement/bids/index')
            ->where('project.reference_no', 'PROC-ROUND-BID-INDEX-A')
            ->where('round.round_no', 1)
            ->has('round.bid_submissions', 0));

    $this->actingAs($contextA['user'])
        ->get(route('procurement.projects.rounds.bids.index', [$contextB['project'], $roundB]))
        ->assertNotFound();
});

test('user can receive one sealed bid from a registered bidder', function () {
    $context = procurementRoundContext('BID-RECEIVE');

    $supplier = Supplier::query()->create([
        'organization_id' => $context['organization']->id,
        'supplier_code' => 'SUP-BID-RECEIVE',
        'legal_name' => 'Bid Receipt Supplier',
        'status' => 'active',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $participation = BidderParticipation::query()->create([
        'procurement_round_id' => $round->id,
        'supplier_id' => $supplier->id,
        'status' => BidderParticipationStatus::Registered->value,
        'supplier_code_snapshot' => $supplier->supplier_code,
        'supplier_name_snapshot' => $supplier->legal_name,
        'registered_at' => '2027-05-01 08:00:00',
        'created_by' => $context['user']->id,
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bids.store', [$context['project'], $round]), [
            'bidder_participation_id' => $participation->id,
            'receipt_reference' => 'RECEIPT-001',
            'package_reference' => 'SEALED-PKG-001',
            'received_at' => '2027-05-10 09:30:00',
        ])
        ->assertRedirect(route('procurement.projects.rounds.bids.index', [$context['project'], $round]));

    $submission = BidSubmission::query()->firstOrFail();

    expect($submission->bidder_participation_id)->toBe($participation->id)
        ->and($submission->getRawOriginal('status'))->toBe(BidSubmissionStatus::Received->value)
        ->and($submission->receipt_reference)->toBe('RECEIPT-001')
        ->and($submission->package_reference)->toBe('SEALED-PKG-001')
        ->and($submission->received_at?->format('Y-m-d H:i:s'))->toBe('2027-05-10 09:30:00')
        ->and($submission->received_by)->toBe($context['user']->id);
});

test('withdrawn or duplicate bidder submissions are rejected', function () {
    $context = procurementRoundContext('BID-GUARDS');

    $supplier = Supplier::query()->create([
        'organization_id' => $context['organization']->id,
        'supplier_code' => 'SUP-BID-GUARDS',
        'legal_name' => 'Bid Guard Supplier',
        'status' => 'active',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $participation = BidderParticipation::query()->create([
        'procurement_round_id' => $round->id,
        'supplier_id' => $supplier->id,
        'status' => BidderParticipationStatus::Registered->value,
        'supplier_code_snapshot' => $supplier->supplier_code,
        'supplier_name_snapshot' => $supplier->legal_name,
        'registered_at' => now(),
        'created_by' => $context['user']->id,
    ]);

    $payload = [
        'bidder_participation_id' => $participation->id,
        'receipt_reference' => 'RECEIPT-GUARD',
    ];

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bids.store', [$context['project'], $round]), $payload)
        ->assertRedirect();

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bids.store', [$context['project'], $round]), $payload)
        ->assertSessionHasErrors(['bidder_participation_id']);

    $participation->update([
        'status' => BidderParticipationStatus::Withdrawn->value,
        'withdrawn_at' => now(),
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bids.store', [$context['project'], $round]), [
            'bidder_participation_id' => $participation->id,
            'receipt_reference' => 'RECEIPT-WITHDRAWN',
        ])
        ->assertSessionHasErrors(['bidder_participation_id']);

    expect(BidSubmission::query()->count())->toBe(1);
});

test('user can open a received bid once and record observed opening data', function () {
    $context = procurementRoundContext('BID-OPEN');

    $supplier = Supplier::query()->create([
        'organization_id' => $context['organization']->id,
        'supplier_code' => 'SUP-BID-OPEN',
        'legal_name' => 'Bid Opening Supplier',
        'status' => 'active',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $participation = BidderParticipation::query()->create([
        'procurement_round_id' => $round->id,
        'supplier_id' => $supplier->id,
        'status' => BidderParticipationStatus::Registered->value,
        'supplier_code_snapshot' => $supplier->supplier_code,
        'supplier_name_snapshot' => $supplier->legal_name,
        'registered_at' => now(),
        'created_by' => $context['user']->id,
    ]);

    $submission = BidSubmission::query()->create([
        'procurement_round_id' => $round->id,
        'bidder_participation_id' => $participation->id,
        'status' => BidSubmissionStatus::Received->value,
        'receipt_reference' => 'OPEN-001',
        'received_at' => '2027-05-10 09:30:00',
        'received_by' => $context['user']->id,
    ]);

    $this->actingAs($context['user'])
        ->patch(route('procurement.projects.rounds.bids.open', [$context['project'], $round, $submission]), [
            'opened_at' => '2027-05-10 10:00:00',
            'bid_amount' => 950000,
            'eligibility_documents_present' => true,
            'technical_documents_present' => true,
            'financial_documents_present' => true,
            'bid_security_present' => false,
            'opening_remarks' => 'Bid opened in public session; no evaluation decision recorded.',
        ])
        ->assertRedirect(route('procurement.projects.rounds.bids.index', [$context['project'], $round]));

    $submission->refresh();
    $round->refresh();

    expect($submission->getRawOriginal('status'))->toBe(BidSubmissionStatus::Opened->value)
        ->and($submission->bid_amount)->toBe('950000.00')
        ->and($submission->eligibility_documents_present)->toBeTrue()
        ->and($submission->technical_documents_present)->toBeTrue()
        ->and($submission->financial_documents_present)->toBeTrue()
        ->and($submission->bid_security_present)->toBeFalse()
        ->and($submission->opened_at?->format('Y-m-d H:i:s'))->toBe('2027-05-10 10:00:00')
        ->and($submission->opened_by)->toBe($context['user']->id)
        ->and($round->bid_opening_at?->format('Y-m-d H:i:s'))->toBe('2027-05-10 10:00:00');

    $this->actingAs($context['user'])
        ->patch(route('procurement.projects.rounds.bids.open', [$context['project'], $round, $submission]), [
            'bid_amount' => 900000,
            'eligibility_documents_present' => true,
            'technical_documents_present' => true,
            'financial_documents_present' => true,
            'bid_security_present' => true,
        ])
        ->assertSessionHasErrors(['submission']);

    expect($submission->fresh()?->bid_amount)->toBe('950000.00');
});

test('failed procurement rounds keep bid receipt and opening read only', function () {
    $context = procurementRoundContext('BID-FAILED');

    $supplier = Supplier::query()->create([
        'organization_id' => $context['organization']->id,
        'supplier_code' => 'SUP-BID-FAILED',
        'legal_name' => 'Failed Bid Supplier',
        'status' => 'active',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.store', $context['project']));

    $round = ProcurementRound::query()->firstOrFail();

    $participation = BidderParticipation::query()->create([
        'procurement_round_id' => $round->id,
        'supplier_id' => $supplier->id,
        'status' => BidderParticipationStatus::Registered->value,
        'supplier_code_snapshot' => $supplier->supplier_code,
        'supplier_name_snapshot' => $supplier->legal_name,
        'registered_at' => now(),
        'created_by' => $context['user']->id,
    ]);

    $submission = BidSubmission::query()->create([
        'procurement_round_id' => $round->id,
        'bidder_participation_id' => $participation->id,
        'status' => BidSubmissionStatus::Received->value,
        'received_at' => now(),
        'received_by' => $context['user']->id,
    ]);

    $secondSupplier = Supplier::query()->create([
        'organization_id' => $context['organization']->id,
        'supplier_code' => 'SUP-BID-FAILED-SECOND',
        'legal_name' => 'Second Failed Bid Supplier',
        'status' => 'active',
    ]);

    $secondParticipation = BidderParticipation::query()->create([
        'procurement_round_id' => $round->id,
        'supplier_id' => $secondSupplier->id,
        'status' => BidderParticipationStatus::Registered->value,
        'supplier_code_snapshot' => $secondSupplier->supplier_code,
        'supplier_name_snapshot' => $secondSupplier->legal_name,
        'registered_at' => now(),
        'created_by' => $context['user']->id,
    ]);

    $round->update([
        'status' => ProcurementRoundStatus::Failed->value,
        'failure_reason' => 'Round formally failed.',
    ]);

    $this->actingAs($context['user'])
        ->post(route('procurement.projects.rounds.bids.store', [$context['project'], $round]), [
            'bidder_participation_id' => $secondParticipation->id,
        ])
        ->assertSessionHasErrors(['bid']);

    $this->actingAs($context['user'])
        ->patch(route('procurement.projects.rounds.bids.open', [$context['project'], $round, $submission]), [
            'bid_amount' => 800000,
            'eligibility_documents_present' => true,
            'technical_documents_present' => true,
            'financial_documents_present' => true,
            'bid_security_present' => true,
        ])
        ->assertSessionHasErrors(['bid']);

    expect($submission->fresh()?->getRawOriginal('status'))->toBe(BidSubmissionStatus::Received->value);
});
