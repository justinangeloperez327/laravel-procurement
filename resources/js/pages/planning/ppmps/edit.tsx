import { Head } from '@inertiajs/react';
import PpmpController from '@/actions/App/Http/Controllers/Planning/PpmpController';
import Heading from '@/components/heading';
import PpmpForm from '@/components/planning/ppmp-form';

type FiscalYear = { id: number; year: number };
type OrganizationalUnit = { id: number; code: string | null; name: string };
type MarketScoping = {
    id: number;
    reference_no: string;
    title: string;
    fiscal_year_id: number;
};
type ProcurementMethod = { id: number; code: string; name: string };
type Category = { value: string; label: string };
type StoredPpmpItem = {
    id: number;
    market_scoping_id: number | null;
    recommended_procurement_method_id: number | null;
    item_no: string;
    title: string;
    description: string | null;
    procurement_category: string;
    quantity: string;
    unit: string;
    estimated_unit_cost: string | null;
    estimated_budget: string;
    funding_source: string | null;
    target_quarter: number | null;
    remarks: string | null;
};
type PpmpDraft = {
    id: number;
    reference_no: string;
    fiscal_year_id: number;
    organizational_unit_id: number;
    title: string;
    version: number;
    items: StoredPpmpItem[];
};

export default function EditPpmp({
    ppmp,
    fiscalYears,
    organizationalUnits,
    marketScopings,
    procurementMethods,
    categories,
}: {
    ppmp: PpmpDraft;
    fiscalYears: FiscalYear[];
    organizationalUnits: OrganizationalUnit[];
    marketScopings: MarketScoping[];
    procurementMethods: ProcurementMethod[];
    categories: Category[];
}) {
    return (
        <>
            <Head title={`Edit ${ppmp.reference_no}`} />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl">
                    <Heading
                        title={`Edit ${ppmp.reference_no}`}
                        description="Update the PPMP header and procurement requirements while the plan remains in draft status."
                    />

                    <div className="bg-card rounded-xl border p-5 shadow-xs md:p-6">
                        <PpmpForm
                            fiscalYears={fiscalYears}
                            organizationalUnits={organizationalUnits}
                            marketScopings={marketScopings}
                            procurementMethods={procurementMethods}
                            categories={categories}
                            ppmp={ppmp}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

EditPpmp.layout = {
    breadcrumbs: [
        {
            title: 'PPMP',
            href: PpmpController.index(),
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
