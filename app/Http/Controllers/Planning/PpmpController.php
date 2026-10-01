<?php

namespace App\Http\Controllers\Planning;

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\MarketScoping;
use App\Domain\Planning\Models\Ppmp;
use App\Domain\Planning\Services\PlanningWorkflowService;
use App\Domain\Procurement\Models\ProcurementMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planning\PpmpRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PpmpController extends Controller
{
    public function index(Request $request, PlanningWorkflowService $workflow): Response
    {
        $user = $this->procurementUser($request);
        $organizationId = $this->organizationId($request);

        $ppmps = Ppmp::query()
            ->where('organization_id', $organizationId)
            ->with([
                'fiscalYear:id,year',
                'organizationalUnit:id,code,name',
            ])
            ->withCount('items')
            ->withSum('items', 'estimated_budget')
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        $ppmps->through(function (Ppmp $ppmp) use ($workflow, $user): Ppmp {
            $ppmp->setAttribute('available_actions', $workflow->availableActions($user, $ppmp));

            return $ppmp;
        });

        return Inertia::render('planning/ppmps/index', [
            'ppmps' => $ppmps,
        ]);
    }

    public function create(Request $request): Response
    {
        $organizationId = $this->organizationId($request);

        return Inertia::render('planning/ppmps/create', $this->formOptions($organizationId));
    }

    public function store(PpmpRequest $request): RedirectResponse
    {
        $user = $this->procurementUser($request);
        $organizationId = $this->organizationId($request);
        $validated = $request->validated();
        /** @var array<int, array<string, mixed>> $items */
        $items = $validated['items'];
        unset($validated['items']);

        DB::transaction(function () use ($validated, $items, $user, $organizationId): void {
            $ppmp = Ppmp::query()->create([
                ...$validated,
                'organization_id' => $organizationId,
                'version' => 1,
                'status' => PlanningStatus::Draft->value,
                'prepared_by' => $user->id,
            ]);

            foreach ($items as $item) {
                $ppmp->items()->create($this->normalizeItem($item));
            }
        });

        return to_route('planning.ppmps.index')
            ->with('success', 'PPMP saved as draft.');
    }

    public function edit(Request $request, Ppmp $ppmp): Response
    {
        $organizationId = $this->organizationId($request);
        $this->guardEditableRecord($ppmp, $organizationId);
        $ppmp->load('items');

        return Inertia::render('planning/ppmps/edit', [
            ...$this->formOptions($organizationId),
            'ppmp' => $ppmp,
        ]);
    }

    public function update(PpmpRequest $request, Ppmp $ppmp): RedirectResponse
    {
        $organizationId = $this->organizationId($request);
        $this->guardEditableRecord($ppmp, $organizationId);
        $validated = $request->validated();
        /** @var array<int, array<string, mixed>> $items */
        $items = $validated['items'];
        unset($validated['items']);

        DB::transaction(function () use ($ppmp, $validated, $items): void {
            $ppmp->update($validated);
            $ppmp->items()->delete();

            foreach ($items as $item) {
                $ppmp->items()->create($this->normalizeItem($item));
            }
        });

        return to_route('planning.ppmps.index')
            ->with('success', 'PPMP updated.');
    }

    /**
     * @return array{
     *     fiscalYears: Collection<int, FiscalYear>,
     *     organizationalUnits: Collection<int, OrganizationalUnit>,
     *     marketScopings: Collection<int, MarketScoping>,
     *     procurementMethods: Collection<int, ProcurementMethod>,
     *     categories: array<int, array{value: string, label: string}>
     * }
     */
    private function formOptions(int $organizationId): array
    {
        return [
            'fiscalYears' => FiscalYear::query()
                ->where('organization_id', $organizationId)
                ->orderByDesc('year')
                ->get(['id', 'year']),
            'organizationalUnits' => OrganizationalUnit::query()
                ->where('organization_id', $organizationId)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'marketScopings' => MarketScoping::query()
                ->where('organization_id', $organizationId)
                ->orderBy('reference_no')
                ->get(['id', 'reference_no', 'title', 'fiscal_year_id']),
            'procurementMethods' => ProcurementMethod::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'categories' => array_map(
                static fn (ProcurementCategory $category): array => [
                    'value' => $category->value,
                    'label' => match ($category) {
                        ProcurementCategory::Goods => 'Goods',
                        ProcurementCategory::Infrastructure => 'Infrastructure Projects',
                        ProcurementCategory::ConsultingServices => 'Consulting Services',
                    },
                ],
                ProcurementCategory::cases(),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private function normalizeItem(array $item): array
    {
        if (($item['estimated_unit_cost'] ?? null) !== null) {
            $item['estimated_budget'] = round(
                (float) $item['quantity'] * (float) $item['estimated_unit_cost'],
                2,
            );
        }

        $item['status'] = PlanningStatus::Draft->value;

        return $item;
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

    private function guardEditableRecord(Ppmp $ppmp, int $organizationId): void
    {
        abort_unless($ppmp->organization_id === $organizationId, 404);
        abort_unless(
            in_array($ppmp->getRawOriginal('status'), [
                PlanningStatus::Draft->value,
                PlanningStatus::Returned->value,
            ], true),
            409,
            'Only draft or returned PPMPs can be edited.',
        );
    }
}
