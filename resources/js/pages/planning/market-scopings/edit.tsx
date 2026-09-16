import { Head } from '@inertiajs/react';
import MarketScopingController from '@/actions/App/Http/Controllers/Planning/MarketScopingController';
import Heading from '@/components/heading';
import MarketScopingForm from '@/components/planning/market-scoping-form';

type Option = {
    id: number;
    year?: number;
    code?: string;
    name?: string;
};

type Category = {
    value: string;
    label: string;
};

type MarketScopingDraft = {
    id: number;
    reference_no: string;
    fiscal_year_id: number;
    organizational_unit_id: number;
    title: string;
    procurement_category: string;
    description: string | null;
    market_findings: string | null;
    recommended_strategy: string | null;
};

export default function EditMarketScoping({
    marketScoping,
    fiscalYears,
    organizationalUnits,
    categories,
}: {
    marketScoping: MarketScopingDraft;
    fiscalYears: Option[];
    organizationalUnits: Option[];
    categories: Category[];
}) {
    return (
        <>
            <Head title={`Edit ${marketScoping.reference_no}`} />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-5xl">
                    <Heading
                        title={`Edit ${marketScoping.reference_no}`}
                        description="Update the draft market scoping record before it enters review."
                    />

                    <div className="bg-card rounded-xl border p-5 shadow-xs md:p-6">
                        <MarketScopingForm
                            fiscalYears={fiscalYears}
                            organizationalUnits={organizationalUnits}
                            categories={categories}
                            marketScoping={marketScoping}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

EditMarketScoping.layout = {
    breadcrumbs: [
        {
            title: 'Market Scoping',
            href: MarketScopingController.index(),
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
