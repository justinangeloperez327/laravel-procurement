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

export default function CreatePpmp({
    fiscalYears,
    organizationalUnits,
    marketScopings,
    procurementMethods,
    categories,
}: {
    fiscalYears: FiscalYear[];
    organizationalUnits: OrganizationalUnit[];
    marketScopings: MarketScoping[];
    procurementMethods: ProcurementMethod[];
    categories: Category[];
}) {
    return (
        <>
            <Head title="New PPMP" />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl">
                    <Heading
                        title="New PPMP"
                        description="Prepare a Project Procurement Management Plan and its planned procurement requirements."
                    />

                    <div className="bg-card rounded-xl border p-5 shadow-xs md:p-6">
                        <PpmpForm
                            fiscalYears={fiscalYears}
                            organizationalUnits={organizationalUnits}
                            marketScopings={marketScopings}
                            procurementMethods={procurementMethods}
                            categories={categories}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

CreatePpmp.layout = {
    breadcrumbs: [
        {
            title: 'PPMP',
            href: PpmpController.index(),
        },
        {
            title: 'New',
            href: PpmpController.create(),
        },
    ],
};
