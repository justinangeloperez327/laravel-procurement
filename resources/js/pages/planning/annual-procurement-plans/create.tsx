import { Head } from '@inertiajs/react';
import AnnualProcurementPlanController from '@/actions/App/Http/Controllers/Planning/AnnualProcurementPlanController';
import Heading from '@/components/heading';
import AnnualProcurementPlanForm from '@/components/planning/annual-procurement-plan-form';

type FiscalYear = { id: number; year: number };
type ProcurementMethod = { id: number; code: string; name: string };
type AppType = { value: string; label: string };
type PpmpItemSource = {
    id: number;
    ppmp_id: number;
    recommended_procurement_method_id: number | null;
    item_no: string;
    title: string;
    description: string | null;
    procurement_category: string;
    estimated_budget: string;
    funding_source: string | null;
    target_quarter: number | null;
    ppmp: {
        id: number;
        reference_no: string;
        fiscal_year_id: number;
        status: string;
        organizational_unit: {
            id: number;
            code: string | null;
            name: string;
        };
    };
    recommended_procurement_method: ProcurementMethod | null;
};

export default function CreateAnnualProcurementPlan({
    fiscalYears,
    ppmpItems,
    procurementMethods,
    appTypes,
    bidEvaluationCriteria,
    procurementStrategyTools,
}: {
    fiscalYears: FiscalYear[];
    ppmpItems: PpmpItemSource[];
    procurementMethods: ProcurementMethod[];
    appTypes: AppType[];
    bidEvaluationCriteria: string[];
    procurementStrategyTools: string[];
}) {
    return (
        <>
            <Head title="New Annual Procurement Plan" />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl">
                    <Heading
                        title="New Annual Procurement Plan"
                        description="Consolidate eligible PPMP items into the official APP planning structure."
                    />

                    <div className="bg-card rounded-xl border p-5 shadow-xs md:p-6">
                        <AnnualProcurementPlanForm
                            fiscalYears={fiscalYears}
                            ppmpItems={ppmpItems}
                            procurementMethods={procurementMethods}
                            appTypes={appTypes}
                            bidEvaluationCriteria={bidEvaluationCriteria}
                            procurementStrategyTools={
                                procurementStrategyTools
                            }
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

CreateAnnualProcurementPlan.layout = {
    breadcrumbs: [
        {
            title: 'Annual Procurement Plan',
            href: AnnualProcurementPlanController.index(),
        },
        {
            title: 'New',
            href: AnnualProcurementPlanController.create(),
        },
    ],
};
