import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import AnnualProcurementPlanController from '@/actions/App/Http/Controllers/Planning/AnnualProcurementPlanController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type AnnualProcurementPlan = {
    id: number;
    reference_no: string;
    app_type: string;
    version: number;
    status: string;
    items_count: number;
    items_sum_estimated_budget: string | null;
    fiscal_year: {
        id: number;
        year: number;
    };
};

type PaginatedPlans = {
    data: AnnualProcurementPlan[];
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

function humanize(value: string) {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function currency(value: string | null) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value) || 0);
}

export default function AnnualProcurementPlanIndex({
    plans,
}: {
    plans: PaginatedPlans;
}) {
    return (
        <>
            <Head title="Annual Procurement Plan" />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <Heading
                            title="Annual Procurement Plans"
                            description="Consolidate eligible PPMP requirements into Indicative, Final, or Updated APPs for the annual procurement cycle."
                        />

                        <Button asChild>
                            <Link
                                href={AnnualProcurementPlanController.create()}
                            >
                                <Plus />
                                New APP
                            </Link>
                        </Button>
                    </div>

                    <div className="bg-card overflow-hidden rounded-xl border shadow-xs">
                        {plans.data.length === 0 ? (
                            <div className="flex min-h-64 flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                                <div>
                                    <p className="font-medium">
                                        No Annual Procurement Plans yet
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Create an APP after PPMPs are ready for
                                        consolidation.
                                    </p>
                                </div>
                                <Button asChild variant="outline">
                                    <Link
                                        href={AnnualProcurementPlanController.create()}
                                    >
                                        <Plus />
                                        Create APP
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 text-left">
                                        <tr className="border-b">
                                            <th className="px-4 py-3 font-medium">
                                                Reference
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Type
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                FY
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Projects
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Budget
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Status
                                            </th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {plans.data.map((plan) => (
                                            <tr
                                                key={plan.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                    {plan.reference_no}
                                                    <span className="text-muted-foreground ml-2 text-xs">
                                                        v{plan.version}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    {humanize(plan.app_type)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {plan.fiscal_year.year}
                                                </td>
                                                <td className="px-4 py-3 text-right tabular-nums">
                                                    {plan.items_count}
                                                </td>
                                                <td className="px-4 py-3 text-right font-medium whitespace-nowrap tabular-nums">
                                                    {currency(
                                                        plan.items_sum_estimated_budget,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="secondary">
                                                        {humanize(plan.status)}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {plan.status === 'draft' ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={AnnualProcurementPlanController.edit(
                                                                    plan.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
                                                        </Button>
                                                    ) : (
                                                        <span className="text-muted-foreground text-xs">
                                                            Locked
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    {plans.total > 0 && (
                        <div className="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground">
                                Showing {plans.from}–{plans.to} of {plans.total}
                            </p>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!plans.prev_page_url}
                                    asChild={Boolean(plans.prev_page_url)}
                                >
                                    {plans.prev_page_url ? (
                                        <Link
                                            href={plans.prev_page_url}
                                            preserveScroll
                                        >
                                            Previous
                                        </Link>
                                    ) : (
                                        <span>Previous</span>
                                    )}
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!plans.next_page_url}
                                    asChild={Boolean(plans.next_page_url)}
                                >
                                    {plans.next_page_url ? (
                                        <Link
                                            href={plans.next_page_url}
                                            preserveScroll
                                        >
                                            Next
                                        </Link>
                                    ) : (
                                        <span>Next</span>
                                    )}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

AnnualProcurementPlanIndex.layout = {
    breadcrumbs: [
        {
            title: 'Annual Procurement Plan',
            href: AnnualProcurementPlanController.index(),
        },
    ],
};
