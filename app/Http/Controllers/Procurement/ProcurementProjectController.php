<?php

namespace App\Http\Controllers\Procurement;

use App\Domain\Planning\Enums\AnnualProcurementPlanType;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Models\AppItem;
use App\Domain\Procurement\Enums\ProcurementStatus;
use App\Domain\Procurement\Models\ProcurementProject;
use App\Http\Controllers\Controller;
use App\Http\Requests\Procurement\ProcurementProjectStoreRequest;
use App\Http\Requests\Procurement\ProcurementProjectUpdateRequest;
use App\Models\User;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProcurementProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $organizationId = $this->organizationId($request);

        $projects = ProcurementProject::query()
            ->where('organization_id', $organizationId)
            ->with([
                'fiscalYear:id,year',
                'procurementMethod:id,code,name',
                'procurementOfficer:id,name',
                'appItem:id,annual_procurement_plan_id,app_item_no,title',
                'appItem.annualProcurementPlan:id,reference_no,version,app_type',
            ])
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('procurement/projects/index', [
            'projects' => $projects,
        ]);
    }

    public function create(Request $request): Response
    {
        $organizationId = $this->organizationId($request);

        return Inertia::render('procurement/projects/create', [
            'appItems' => $this->eligibleAppItems($organizationId),
            'procurementOfficers' => $this->organizationUsers($organizationId),
        ]);
    }

    public function store(ProcurementProjectStoreRequest $request): RedirectResponse
    {
        $user = $this->procurementUser($request);
        $organizationId = $this->organizationId($request);
        $validated = $request->validated();

        $source = AppItem::query()
            ->with('annualProcurementPlan')
            ->whereKey($validated['app_item_id'])
            ->whereHas('annualProcurementPlan', fn ($query) => $query
                ->where('organization_id', $organizationId))
            ->firstOrFail();

        $this->guardEligibleSource($source);
        $this->guardProcurementOfficer($validated['procurement_officer_id'] ?? null, $organizationId);
        $this->guardUniqueReference($validated['reference_no'], $organizationId);
        $this->guardBudget($source, (string) $validated['approved_budget']);

        $plan = $source->annualProcurementPlan;

        ProcurementProject::query()->create([
            'organization_id' => $organizationId,
            'fiscal_year_id' => $plan->fiscal_year_id,
            'app_item_id' => $source->id,
            'procurement_method_id' => $source->procurement_method_id,
            'reference_no' => $validated['reference_no'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? $source->description,
            'procurement_category' => $source->getRawOriginal('procurement_category'),
            'approved_budget' => $validated['approved_budget'],
            'funding_source' => $source->funding_source,
            'status' => ProcurementStatus::Planned->value,
            'current_stage' => 'initiation',
            'created_by' => $user->id,
            'procurement_officer_id' => $validated['procurement_officer_id'] ?? null,
            'target_start_date' => $validated['target_start_date'] ?? null,
            'target_completion_date' => $validated['target_completion_date'] ?? null,
        ]);

        return to_route('procurement.projects.index')
            ->with('success', 'Procurement project initiated from the approved APP.');
    }

    public function edit(Request $request, ProcurementProject $project): Response
    {
        $organizationId = $this->organizationId($request);
        $this->guardEditableProject($project, $organizationId);
        $project->load([
            'appItem.annualProcurementPlan:id,reference_no,version,app_type',
            'procurementMethod:id,code,name',
        ]);

        return Inertia::render('procurement/projects/edit', [
            'project' => $project,
            'remainingBudget' => $this->remainingBudget($project->appItem, $project),
            'procurementOfficers' => $this->organizationUsers($organizationId),
        ]);
    }

    public function update(
        ProcurementProjectUpdateRequest $request,
        ProcurementProject $project,
    ): RedirectResponse {
        $organizationId = $this->organizationId($request);
        $this->guardEditableProject($project, $organizationId);
        $validated = $request->validated();

        $this->guardProcurementOfficer($validated['procurement_officer_id'] ?? null, $organizationId);
        $this->guardUniqueReference($validated['reference_no'], $organizationId, $project);
        $this->guardBudget($project->appItem, (string) $validated['approved_budget'], $project);

        $project->update($validated);

        return to_route('procurement.projects.index')
            ->with('success', 'Procurement project updated.');
    }

    /**
     * @return Collection<int, AppItem>
     */
    private function eligibleAppItems(int $organizationId): Collection
    {
        $items = AppItem::query()
            ->with([
                'annualProcurementPlan:id,organization_id,fiscal_year_id,reference_no,version,app_type,status',
                'procurementMethod:id,code,name',
            ])
            ->whereHas('annualProcurementPlan', fn ($query) => $query
                ->where('organization_id', $organizationId)
                ->where('status', PlanningStatus::Approved->value))
            ->orderBy('app_item_no')
            ->get();

        return $items
            ->filter(fn (AppItem $item): bool => $this->isEligibleSource($item))
            ->each(function (AppItem $item): void {
                $item->setAttribute('remaining_budget', $this->remainingBudget($item));
            })
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    private function organizationUsers(int $organizationId): Collection
    {
        return User::query()
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'position_title']);
    }

    private function guardEligibleSource(AppItem $item): void
    {
        if (! $this->isEligibleSource($item)) {
            throw ValidationException::withMessages([
                'app_item_id' => 'The selected APP item is not eligible for procurement initiation.',
            ]);
        }
    }

    private function isEligibleSource(AppItem $item): bool
    {
        $plan = $item->annualProcurementPlan;

        if ($plan->getRawOriginal('status') !== PlanningStatus::Approved->value) {
            return false;
        }

        if ($plan->getRawOriginal('app_type') === AnnualProcurementPlanType::Indicative->value) {
            return $item->is_early_procurement_activity === true;
        }

        return true;
    }

    private function guardBudget(
        AppItem $item,
        string $requestedBudget,
        ?ProcurementProject $excluding = null,
    ): void {
        $remaining = BigDecimal::of($this->remainingBudget($item, $excluding));

        if (BigDecimal::of($requestedBudget)->isGreaterThan($remaining)) {
            throw ValidationException::withMessages([
                'approved_budget' => 'The project budget exceeds the unallocated budget of the selected APP item.',
            ]);
        }
    }

    private function remainingBudget(AppItem $item, ?ProcurementProject $excluding = null): string
    {
        $allocated = ProcurementProject::query()
            ->where('app_item_id', $item->id)
            ->where('status', '!=', ProcurementStatus::Cancelled->value)
            ->when($excluding !== null, fn ($query) => $query->whereKeyNot($excluding->id))
            ->sum('approved_budget');

        return (string) BigDecimal::of((string) $item->estimated_budget)
            ->minus((string) $allocated)
            ->toScale(2);
    }

    private function guardProcurementOfficer(?int $userId, int $organizationId): void
    {
        if ($userId === null) {
            return;
        }

        $valid = User::query()
            ->whereKey($userId)
            ->where('organization_id', $organizationId)
            ->where('is_active', true)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'procurement_officer_id' => 'The selected procurement officer is not available in this organization.',
            ]);
        }
    }

    private function guardUniqueReference(
        string $referenceNo,
        int $organizationId,
        ?ProcurementProject $excluding = null,
    ): void {
        $exists = ProcurementProject::query()
            ->where('organization_id', $organizationId)
            ->where('reference_no', $referenceNo)
            ->when($excluding !== null, fn ($query) => $query->whereKeyNot($excluding->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'reference_no' => 'This procurement project reference is already in use.',
            ]);
        }
    }

    private function guardEditableProject(ProcurementProject $project, int $organizationId): void
    {
        abort_unless($project->organization_id === $organizationId, 404);
        abort_unless(
            $project->getRawOriginal('status') === ProcurementStatus::Planned->value,
            409,
            'Only planned procurement projects can be edited.',
        );
    }

    private function organizationId(Request $request): int
    {
        $organizationId = $this->procurementUser($request)->organization_id;

        if ($organizationId === null) {
            abort(403, 'An organization assignment is required.');
        }

        return $organizationId;
    }

    private function procurementUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User || $user->organization_id === null) {
            abort(403, 'An organization assignment is required.');
        }

        return $user;
    }
}
