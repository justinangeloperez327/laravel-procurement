import { Head, Link } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import MarketScopingController from '@/actions/App/Http/Controllers/Planning/MarketScopingController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type MarketScoping = {
    id: number;
    reference_no: string;
    title: string;
    procurement_category: string;
    status: string;
    updated_at: string;
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

type PaginatedMarketScopings = {
    data: MarketScoping[];
    current_page: number;
    last_page: number;
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

export default function MarketScopingIndex({
    marketScopings,
}: {
    marketScopings: PaginatedMarketScopings;
}) {
    return (
        <>
            <Head title="Market Scoping" />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <Heading
                            title="Market Scoping"
                            description="Build and maintain the market basis used for procurement planning."
                        />

                        <Button asChild>
                            <Link href={MarketScopingController.create()}>
                                <Plus />
                                New market scoping
                            </Link>
                        </Button>
                    </div>

                    <div className="bg-card overflow-hidden rounded-xl border shadow-xs">
                        {marketScopings.data.length === 0 ? (
                            <div className="flex min-h-64 flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                                <div>
                                    <p className="font-medium">
                                        No market scoping records yet
                                    </p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Create the first market scoping record
                                        to begin procurement planning.
                                    </p>
                                </div>
                                <Button asChild variant="outline">
                                    <Link
                                        href={MarketScopingController.create()}
                                    >
                                        <Plus />
                                        Create record
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
                                                Requirement
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Fiscal Year
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Unit
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Category
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
                                        {marketScopings.data.map((item) => (
                                            <tr
                                                key={item.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                    {item.reference_no}
                                                </td>
                                                <td className="max-w-sm px-4 py-3">
                                                    <div className="truncate">
                                                        {item.title}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    {item.fiscal_year.year}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {item.organizational_unit
                                                        .code
                                                        ? `${item.organizational_unit.code} — ${item.organizational_unit.name}`
                                                        : item
                                                              .organizational_unit
                                                              .name}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    {humanize(
                                                        item.procurement_category,
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="secondary">
                                                        {humanize(item.status)}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {item.status === 'draft' ? (
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={MarketScopingController.edit(
                                                                    item.id,
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

                    {marketScopings.total > 0 && (
                        <div className="flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-muted-foreground">
                                Showing {marketScopings.from}–
                                {marketScopings.to} of {marketScopings.total}
                            </p>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={!marketScopings.prev_page_url}
                                    asChild={Boolean(
                                        marketScopings.prev_page_url,
                                    )}
                                >
                                    {marketScopings.prev_page_url ? (
                                        <Link
                                            href={marketScopings.prev_page_url}
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
                                    disabled={!marketScopings.next_page_url}
                                    asChild={Boolean(
                                        marketScopings.next_page_url,
                                    )}
                                >
                                    {marketScopings.next_page_url ? (
                                        <Link
                                            href={marketScopings.next_page_url}
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

MarketScopingIndex.layout = {
    breadcrumbs: [
        {
            title: 'Market Scoping',
            href: MarketScopingController.index(),
        },
    ],
};
