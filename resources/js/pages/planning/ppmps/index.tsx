import { Head, Link, router } from '@inertiajs/react';
import { Check, Pencil, Play, Plus, RotateCcw, Send } from 'lucide-react';
import PpmpController from '@/actions/App/Http/Controllers/Planning/PpmpController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type WorkflowAction =
    | 'submitted'
    | 'review_started'
    | 'approved'
    | 'returned';

type Ppmp = {
    id: number;
    reference_no: string;
    title: string;
    version: number;
    status: string;
    available_actions: WorkflowAction[];
    items_count: number;
    items_sum_estimated_budget: string | null;
    fiscal_year: {
        id: number;
        year: number;
    };
    organizational_unit: {
        id: number;
        code: string | null;
        name: string;
    };
};

type PaginatedPpmps = {
    data: Ppmp[];
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

function transition(ppmp: Ppmp, action: WorkflowAction) {
    if (action === 'returned') {
        const remarks = window.prompt('Reason for returning this PPMP:');

        if (!remarks?.trim()) {
            return;
        }

        router.patch(`/planning/ppmps/${ppmp.id}/return`, { remarks });

        return;
    }

    const endpoint =
        action === 'submitted'
            ? 'submit'
            : action === 'review_started'
              ? 'review'
              : 'approve';

    router.patch(`/planning/ppmps/${ppmp.id}/${endpoint}`);
}

function WorkflowButton({ ppmp, action }: { ppmp: Ppmp; action: WorkflowAction }) {
    const config = {
        submitted: { label: 'Submit', icon: Send },
        review_started: { label: 'Start Review', icon: Play },
        approved: { label: 'Approve', icon: Check },
        returned: { label: 'Return', icon: RotateCcw },
    }[action];
    const Icon = config.icon;

    return (
        <Button
            variant={action === 'approved' ? 'default' : 'outline'}
            size="sm"
            onClick={() => transition(ppmp, action)}
        >
            <Icon />
            {config.label}
        </Button>
    );
}

export default function PpmpIndex({ ppmps }: { ppmps: PaginatedPpmps }) {
    return (
        <>
            <Head title="PPMP" />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <Heading
                            title="Project Procurement Management Plans"
                            description="Prepare, submit, review, and approve PPMPs before consolidation into the Annual Procurement Plan."
                        />

                        <Button asChild>
                            <Link href={PpmpController.create()}>
                                <Plus />
                                New PPMP
                            </Link>
                        </Button>
                    </div>

                    <div className="bg-card overflow-hidden rounded-xl border shadow-xs">
                        {ppmps.data.length === 0 ? (
                            <div className="flex min-h-64 flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                                <div>
                                    <p className="font-medium">No PPMPs yet</p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Create a PPMP to begin formal procurement planning for a fiscal year.
                                    </p>
                                </div>
                                <Button asChild variant="outline">
                                    <Link href={PpmpController.create()}>
                                        <Plus />
                                        Create PPMP
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 text-left">
                                        <tr className="border-b">
                                            <th className="px-4 py-3 font-medium">Reference</th>
                                            <th className="px-4 py-3 font-medium">PPMP</th>
                                            <th className="px-4 py-3 font-medium">FY</th>
                                            <th className="px-4 py-3 font-medium">Unit</th>
                                            <th className="px-4 py-3 text-right font-medium">Items</th>
                                            <th className="px-4 py-3 text-right font-medium">Budget</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 text-right font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {ppmps.data.map((ppmp) => {
                                            const editable = ['draft', 'returned'].includes(ppmp.status);

                                            return (
                                                <tr key={ppmp.id} className="border-b last:border-0">
                                                    <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                        {ppmp.reference_no}
                                                        <span className="text-muted-foreground ml-2 text-xs">
                                                            v{ppmp.version}
                                                        </span>
                                                    </td>
                                                    <td className="max-w-sm px-4 py-3">
                                                        <div className="truncate">{ppmp.title}</div>
                                                    </td>
                                                    <td className="px-4 py-3">{ppmp.fiscal_year.year}</td>
                                                    <td className="px-4 py-3">
                                                        {ppmp.organizational_unit.code
                                                            ? `${ppmp.organizational_unit.code} — ${ppmp.organizational_unit.name}`
                                                            : ppmp.organizational_unit.name}
                                                    </td>
                                                    <td className="px-4 py-3 text-right tabular-nums">
                                                        {ppmp.items_count}
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-medium whitespace-nowrap tabular-nums">
                                                        {currency(ppmp.items_sum_estimated_budget)}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge variant="secondary">{humanize(ppmp.status)}</Badge>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="flex flex-wrap justify-end gap-2">
                                                            {editable && (
                                                                <Button variant="outline" size="sm" asChild>
                                                                    <Link href={PpmpController.edit(ppmp.id)}>
                                                                        <Pencil />
                                                                        Edit
                                                                    </Link>
                                                                </Button>
                                                            )}
                                                            {ppmp.available_actions.map((action) => (
                                                                <WorkflowButton
                                                                    key={action}
                                                                    ppmp={ppmp}
                                                                    action={action}
                                                                />
                                                            ))}
                                                            {!editable && ppmp.available_actions.length === 0 && (
                                                                <span className="text-muted-foreground self-center text-xs">
                                                                    No action available
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>

                    {ppmps.total > 0 && (
                        <div className="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground">
                                Showing {ppmps.from}–{ppmps.to} of {ppmps.total}
                            </p>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!ppmps.prev_page_url}
                                    asChild={Boolean(ppmps.prev_page_url)}
                                >
                                    {ppmps.prev_page_url ? (
                                        <Link href={ppmps.prev_page_url} preserveScroll>
                                            Previous
                                        </Link>
                                    ) : (
                                        <span>Previous</span>
                                    )}
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!ppmps.next_page_url}
                                    asChild={Boolean(ppmps.next_page_url)}
                                >
                                    {ppmps.next_page_url ? (
                                        <Link href={ppmps.next_page_url} preserveScroll>
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

PpmpIndex.layout = {
    breadcrumbs: [
        {
            title: 'PPMP',
            href: PpmpController.index(),
        },
    ],
};
