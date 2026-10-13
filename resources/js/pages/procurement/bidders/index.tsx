import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, UserPlus, UserX } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Supplier = {
    id: number;
    supplier_code: string;
    legal_name: string;
    trade_name: string | null;
    philgeps_registration_no: string | null;
    email: string | null;
    phone: string | null;
};

type Participation = {
    id: number;
    status: string;
    supplier_code_snapshot: string;
    supplier_name_snapshot: string;
    philgeps_registration_no_snapshot: string | null;
    representative_name: string | null;
    representative_email: string | null;
    representative_phone: string | null;
    registered_at: string;
    withdrawn_at: string | null;
    supplier: Supplier;
};

type Round = {
    id: number;
    round_no: number;
    status: string;
    procurement_method: {
        id: number;
        code: string;
        name: string;
    };
    bidder_participations: Participation[];
};

type Project = {
    id: number;
    reference_no: string;
    title: string;
};

function humanize(value: string) {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

function dateTime(value: string | null) {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-PH', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

export default function BidderParticipationIndex({
    project,
    round,
    availableSuppliers,
    canModify,
}: {
    project: Project;
    round: Round;
    availableSuppliers: Supplier[];
    canModify: boolean;
}) {
    const form = useForm({
        supplier_id: '',
        representative_name: '',
        representative_email: '',
        representative_phone: '',
    });

    const baseUrl = `/procurement/projects/${project.id}/rounds/${round.id}/bidders`;

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(baseUrl, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const withdraw = (participation: Participation) => {
        router.patch(
            `${baseUrl}/${participation.id}/withdraw`,
            {},
            { preserveScroll: true },
        );
    };

    return (
        <>
            <Head
                title={`Bidders - ${project.reference_no} - Round ${round.round_no}`}
            />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <div className="space-y-3">
                        <Button asChild variant="ghost" size="sm">
                            <Link
                                href={`/procurement/projects/${project.id}/rounds`}
                            >
                                <ArrowLeft />
                                Procurement Rounds
                            </Link>
                        </Button>
                        <Heading
                            title={`${project.reference_no} — Round #${round.round_no} Bidders`}
                            description="Register participating suppliers while preserving the supplier identity captured for this procurement round."
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="bg-card rounded-xl border p-4 shadow-xs">
                            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                Project
                            </p>
                            <p className="mt-2 font-semibold">
                                {project.title}
                            </p>
                        </div>
                        <div className="bg-card rounded-xl border p-4 shadow-xs">
                            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                Procurement Method
                            </p>
                            <p className="mt-2 font-semibold">
                                {round.procurement_method.name}
                            </p>
                        </div>
                    </div>

                    {canModify ? (
                        <form
                            onSubmit={submit}
                            className="bg-card grid gap-4 rounded-xl border p-5 shadow-xs md:grid-cols-2 md:p-6 xl:grid-cols-4"
                        >
                            <div className="space-y-2 md:col-span-2 xl:col-span-4">
                                <label
                                    htmlFor="supplier_id"
                                    className="text-sm font-medium"
                                >
                                    Supplier
                                </label>
                                <select
                                    id="supplier_id"
                                    value={form.data.supplier_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'supplier_id',
                                            event.target.value,
                                        )
                                    }
                                    className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs"
                                >
                                    <option value="">
                                        Select an active supplier
                                    </option>
                                    {availableSuppliers.map((supplier) => (
                                        <option
                                            key={supplier.id}
                                            value={supplier.id}
                                        >
                                            {supplier.supplier_code} —{' '}
                                            {supplier.legal_name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.supplier_id && (
                                    <p className="text-destructive text-xs">
                                        {form.errors.supplier_id}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label
                                    htmlFor="representative_name"
                                    className="text-sm font-medium"
                                >
                                    Representative
                                </label>
                                <Input
                                    id="representative_name"
                                    value={form.data.representative_name}
                                    onChange={(event) =>
                                        form.setData(
                                            'representative_name',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Contact person"
                                />
                            </div>

                            <div className="space-y-2">
                                <label
                                    htmlFor="representative_email"
                                    className="text-sm font-medium"
                                >
                                    Email
                                </label>
                                <Input
                                    id="representative_email"
                                    type="email"
                                    value={form.data.representative_email}
                                    onChange={(event) =>
                                        form.setData(
                                            'representative_email',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="space-y-2">
                                <label
                                    htmlFor="representative_phone"
                                    className="text-sm font-medium"
                                >
                                    Phone
                                </label>
                                <Input
                                    id="representative_phone"
                                    value={form.data.representative_phone}
                                    onChange={(event) =>
                                        form.setData(
                                            'representative_phone',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>

                            <div className="flex items-end">
                                <Button
                                    type="submit"
                                    className="w-full"
                                    disabled={form.processing}
                                >
                                    <UserPlus />
                                    Register Bidder
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <div className="bg-muted/40 rounded-xl border px-5 py-4 text-sm">
                            This round is historical or terminal. Bidder
                            participation is read-only.
                        </div>
                    )}

                    <div className="bg-card overflow-hidden rounded-xl border shadow-xs">
                        {round.bidder_participations.length === 0 ? (
                            <div className="flex min-h-56 flex-col items-center justify-center gap-2 px-6 py-12 text-center">
                                <p className="font-medium">
                                    No bidders registered
                                </p>
                                <p className="text-muted-foreground max-w-2xl text-sm">
                                    Register suppliers that participate in this
                                    procurement round. Eligibility and bid
                                    evaluation decisions are recorded in later
                                    workflow modules.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 text-left">
                                        <tr className="border-b">
                                            <th className="px-4 py-3 font-medium">
                                                Supplier
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                PhilGEPS
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Representative
                                            </th>
                                            <th className="px-4 py-3 font-medium">
                                                Registered
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
                                        {round.bidder_participations.map(
                                            (participation) => (
                                                <tr
                                                    key={participation.id}
                                                    className="border-b align-top last:border-0"
                                                >
                                                    <td className="px-4 py-3">
                                                        <div className="font-medium">
                                                            {
                                                                participation.supplier_name_snapshot
                                                            }
                                                        </div>
                                                        <div className="text-muted-foreground text-xs">
                                                            {
                                                                participation.supplier_code_snapshot
                                                            }
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        {participation.philgeps_registration_no_snapshot ??
                                                            '—'}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div>
                                                            {participation.representative_name ??
                                                                '—'}
                                                        </div>
                                                        {participation.representative_email && (
                                                            <div className="text-muted-foreground text-xs">
                                                                {
                                                                    participation.representative_email
                                                                }
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap">
                                                        {dateTime(
                                                            participation.registered_at,
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <Badge variant="outline">
                                                            {humanize(
                                                                participation.status,
                                                            )}
                                                        </Badge>
                                                        {participation.withdrawn_at && (
                                                            <div className="text-muted-foreground mt-1 text-xs">
                                                                {dateTime(
                                                                    participation.withdrawn_at,
                                                                )}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        {canModify &&
                                                            participation.status ===
                                                                'registered' && (
                                                                <Button
                                                                    type="button"
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() =>
                                                                        withdraw(
                                                                            participation,
                                                                        )
                                                                    }
                                                                >
                                                                    <UserX />
                                                                    Withdraw
                                                                </Button>
                                                            )}
                                                    </td>
                                                </tr>
                                            ),
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
