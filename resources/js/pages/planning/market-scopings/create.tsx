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

export default function CreateMarketScoping({
    fiscalYears,
    organizationalUnits,
    categories,
}: {
    fiscalYears: Option[];
    organizationalUnits: Option[];
    categories: Category[];
}) {
    return (
        <>
            <Head title="New Market Scoping" />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-5xl">
                    <Heading
                        title="New Market Scoping"
                        description="Document the market basis for a planned procurement before PPMP preparation."
                    />

                    <div className="bg-card rounded-xl border p-5 shadow-xs md:p-6">
                        <MarketScopingForm
                            fiscalYears={fiscalYears}
                            organizationalUnits={organizationalUnits}
                            categories={categories}
                        />
                    </div>
                </div>
            </div>
        </>
    );
}

CreateMarketScoping.layout = {
    breadcrumbs: [
        {
            title: 'Market Scoping',
            href: MarketScopingController.index(),
        },
        {
            title: 'New',
            href: MarketScopingController.create(),
        },
    ],
};
