<?php

namespace App\Http\Controllers\Planning;

use App\Domain\Planning\Enums\PlanningWorkflowAction;
use App\Domain\Planning\Models\AnnualProcurementPlan;
use App\Domain\Planning\Models\Ppmp;
use App\Domain\Planning\Services\PlanningWorkflowService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanningWorkflowController extends Controller
{
    public function submitPpmp(
        Request $request,
        Ppmp $ppmp,
        PlanningWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->transition(
            $this->procurementUser($request),
            $ppmp,
            PlanningWorkflowAction::Submitted,
        );

        return back()->with('success', 'PPMP submitted for review.');
    }

    public function startPpmpReview(
        Request $request,
        Ppmp $ppmp,
        PlanningWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->transition(
            $this->procurementUser($request),
            $ppmp,
            PlanningWorkflowAction::ReviewStarted,
        );

        return back()->with('success', 'PPMP review started.');
    }

    public function approvePpmp(
        Request $request,
        Ppmp $ppmp,
        PlanningWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->transition(
            $this->procurementUser($request),
            $ppmp,
            PlanningWorkflowAction::Approved,
        );

        return back()->with('success', 'PPMP approved.');
    }

    public function returnPpmp(
        Request $request,
        Ppmp $ppmp,
        PlanningWorkflowService $workflow,
    ): RedirectResponse {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:2000'],
        ]);

        $workflow->transition(
            $this->procurementUser($request),
            $ppmp,
            PlanningWorkflowAction::Returned,
            $validated['remarks'],
        );

        return back()->with('success', 'PPMP returned for revision.');
    }

    public function submitAnnualProcurementPlan(
        Request $request,
        AnnualProcurementPlan $annualProcurementPlan,
        PlanningWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->transition(
            $this->procurementUser($request),
            $annualProcurementPlan,
            PlanningWorkflowAction::Submitted,
        );

        return back()->with('success', 'Annual Procurement Plan submitted to the BAC.');
    }

    public function recommendAnnualProcurementPlan(
        Request $request,
        AnnualProcurementPlan $annualProcurementPlan,
        PlanningWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->transition(
            $this->procurementUser($request),
            $annualProcurementPlan,
            PlanningWorkflowAction::Recommended,
        );

        return back()->with('success', 'Annual Procurement Plan recommended for HoPE approval.');
    }

    public function approveAnnualProcurementPlan(
        Request $request,
        AnnualProcurementPlan $annualProcurementPlan,
        PlanningWorkflowService $workflow,
    ): RedirectResponse {
        $workflow->transition(
            $this->procurementUser($request),
            $annualProcurementPlan,
            PlanningWorkflowAction::Approved,
        );

        return back()->with('success', 'Annual Procurement Plan approved.');
    }

    public function returnAnnualProcurementPlan(
        Request $request,
        AnnualProcurementPlan $annualProcurementPlan,
        PlanningWorkflowService $workflow,
    ): RedirectResponse {
        $validated = $request->validate([
            'remarks' => ['required', 'string', 'max:2000'],
        ]);

        $workflow->transition(
            $this->procurementUser($request),
            $annualProcurementPlan,
            PlanningWorkflowAction::Returned,
            $validated['remarks'],
        );

        return back()->with('success', 'Annual Procurement Plan returned for revision.');
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
