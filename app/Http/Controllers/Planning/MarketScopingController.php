<?php

namespace App\Http\Controllers\Planning;

use App\Domain\Organization\Models\FiscalYear;
use App\Domain\Organization\Models\OrganizationalUnit;
use App\Domain\Planning\Enums\PlanningStatus;
use App\Domain\Planning\Enums\ProcurementCategory;
use App\Domain\Planning\Models\MarketScoping;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planning\MarketScopingRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketScopingController extends Controller
{
    public function index(Request $request): Response
    {
        $organizationId = $this->organizationId($request);

        $marketScopings = MarketScoping::query()
            ->where('organization_id', $organizationId)
            ->with([
                'fiscalYear:id,year',
                'organizationalUnit:id,code,name',
            ])
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('planning/market-scopings/index', [
            'marketScopings' => $marketScopings,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('planning/market-scopings/create', $this->formOptions(
            $this->organizationId($request),
        ));
    }

    public function store(MarketScopingRequest $request): RedirectResponse
    {
        $user = $this->procurementUser($request);
        $data = $request->validated();
        $data['organization_id'] = $this->organizationId($request);
        $data['prepared_by'] = $user->id;
        $data['status'] = PlanningStatus::Draft->value;

        MarketScoping::query()->create($data);

        return to_route('planning.market-scopings.index')
            ->with('success', 'Market scoping saved as draft.');
    }

    public function edit(Request $request, MarketScoping $marketScoping): Response
    {
        $organizationId = $this->organizationId($request);
        $this->guardEditableRecord($marketScoping, $organizationId);

        return Inertia::render('planning/market-scopings/edit', [
            ...$this->formOptions($organizationId),
            'marketScoping' => $marketScoping,
        ]);
    }

    public function update(
        MarketScopingRequest $request,
        MarketScoping $marketScoping,
    ): RedirectResponse {
        $organizationId = $this->organizationId($request);
        $this->guardEditableRecord($marketScoping, $organizationId);

        $marketScoping->update($request->validated());

        return to_route('planning.market-scopings.index')
            ->with('success', 'Market scoping draft updated.');
    }

    /**
     * @return array{
     *     fiscalYears: Collection<int, FiscalYear>,
     *     organizationalUnits: Collection<int, OrganizationalUnit>,
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

    private function guardEditableRecord(MarketScoping $marketScoping, int $organizationId): void
    {
        abort_unless($marketScoping->organization_id === $organizationId, 404);
        abort_unless(
            $marketScoping->getRawOriginal('status') === PlanningStatus::Draft->value,
            409,
            'Only draft market scopings can be edited.',
        );
    }
}
