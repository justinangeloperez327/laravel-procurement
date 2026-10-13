import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, PackageCheck } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

type Participation = {
    id: number;
    supplier_code_snapshot: string;
    supplier_name_snapshot: string;
    philgeps_registration_no_snapshot: string | null;
};

type UserRef = {
    id: number;
    name: string;
};

type BidSubmission = {
    id: number;
    status: string;
    receipt_reference: string | null;
    package_reference: string | null;
    received_at: string;
    opened_at: string | null;
    bid_amount: string | null;
    currency: string;
    eligibility_documents_present: boolean | null;
    technical_documents_present: boolean | null;
    financial_documents_present: boolean | null;
    bid_security_present: boolean | null;
    opening_remarks: string | null;
    bidder_participation: Participation;
    receiver: UserRef;
    opener: UserRef | null;
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
    bid_submissions: BidSubmission[];
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

function money(value: string | null, currency: string) {
    if (value === null) {
        return '—';
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency,
    }).format(Number(value));
}

function OpeningForm({
    projectId,
    roundId,
    submission,
}: {
    projectId: number;
    roundId: number;
    submission: BidSubmission;
}) {
    const form = useForm({
        opened_at: '',
        bid_amount: '',
        eligibility_documents_present: false,
        technical_documents_present: false,
        financial_documents_present: false,
        bid_security_present: false,
        opening_remarks: '',
    });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.patch(
            `/procurement/projects/${projectId}/rounds/${roundId}/bids/${submission.id}/open`,
            { preserveScroll: true },
        );
    };

    return (
        <form
            onSubmit={submit}
            className="grid gap-3 border-t p-4 md:grid-cols-2 xl:grid-cols-4"
        >
            <div className="space-y-2">
                <label
                    className="text-xs font-medium"
                    htmlFor={`opened-at-${submission.id}`}
                >
                    Opened at
                </label>
                <Input
                    id={`opened-at-${submission.id}`}
                    type="datetime-local"
                    value={form.data.opened_at}
                    onChange={(event) =>
                        form.setData('opened_at', event.target.value)
                    }
                />
            </div>
            <div className="space-y-2">
                <label
                    className="text-xs font-medium"
                    htmlFor={`amount-${submission.id}`}
                >
                    Bid amount
                </label>
                <Input
                    id={`amount-${submission.id}`}
                    type="number"
                    min="0"
                    step="0.01"
                    value={form.data.bid_amount}
                    onChange={(event) =>
                        form.setData('bid_amount', event.target.value)
                    }
                    placeholder="0.00"
                />
            </div>
            <div className="space-y-2 md:col-span-2">
                <label
                    className="text-xs font-medium"
                    htmlFor={`remarks-${submission.id}`}
                >
                    Opening remarks
                </label>
                <Input
                    id={`remarks-${submission.id}`}
                    value={form.data.opening_remarks}
                    onChange={(event) =>
                        form.setData('opening_remarks', event.target.value)
                    }
                    placeholder="Observed facts only; evaluation is recorded separately"
                />
            </div>

            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={form.data.eligibility_documents_present}
                    onChange={(event) =>
                        form.setData(
                            'eligibility_documents_present',
                            event.target.checked,
                        )
                    }
                />
                Eligibility documents present
            </label>
            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={form.data.technical_documents_present}
                    onChange={(event) =>
                        form.setData(
                            'technical_documents_present',
                            event.target.checked,
                        )
                    }
                />
                Technical documents present
            </label>
            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={form.data.financial_documents_present}
                    onChange={(event) =>
                        form.setData(
                            'financial_documents_present',
                            event.target.checked,
                        )
                    }
                />
                Financial documents present
            </label>
            <label className="flex items-center gap-2 text-sm">
                <input
                    type="checkbox"
                    checked={form.data.bid_security_present}
                    onChange={(event) =>
                        form.setData(
                            'bid_security_present',
                            event.target.checked,
                        )
                    }
                />
                Bid security present
            </label>

            <div className="md:col-span-2 xl:col-span-4">
                <Button
                    type="submit"
                    size="sm"
                    disabled={form.processing}
                >
                    <PackageCheck />
                    Record Bid Opening
                </Button>
            </div>
        </form>
    );
}

export default function BidSubmissionIndex({
    project,
    round,
    eligibleParticipations,
    canModify,
}: {
    project: Project;
    round: Round;
    eligibleParticipations: Participation[];
    canModify: boolean;
}) {
    const form = useForm({
        bidder_participation_id: '',
        receipt_reference: '',
        package_reference: '',
        received_at: '',
    });

    const baseUrl = `/procurement/projects/${project.id}/rounds/${round.id}/bids`;

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(baseUrl, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head
                title={`Bids - ${project.reference_no} - Round ${round.round_no}`}
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
                            title={`${project.reference_no} — Round #${round.round_no} Bid Receipt & Opening`}
                            description="Record sealed bid receipt and the facts observed during opening. Eligibility and responsiveness decisions remain separate."
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
                            <div className="space-y-2 md:col-span-2">
                                <label
                                    htmlFor="bidder_participation_id"
                                    className="text-sm font-medium"
                                >
                                    Registered bidder
                                </label>
                                <select
                                    id="bidder_participation_id"
                                    value={form.data.bidder_participation_id}
                                    onChange={(event) =>
                                        form.setData(
                                            'bidder_participation_id',
                                            event.target.value,
                                        )
                                    }
                                    className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs"
                                >
                                    <option value="">Select bidder</option>
                                    {eligibleParticipations.map(
                                        (participation) => (
                                            <option
                                                key={participation.id}
                                                value={participation.id}
                                            >
                                                {
                                                    participation.supplier_code_snapshot
                                                }{' '}
                                                —{' '}
                                                {
                                                    participation.supplier_name_snapshot
                                                }
                                            </option>
                                        ),
                                    )}
                                </select>
                                {form.errors.bidder_participation_id && (
                                    <p className="text-destructive text-xs">
                                        {
                                            form.errors
                                                .bidder_participation_id
                                        }
                                    </p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <label
                                    htmlFor="receipt_reference"
                                    className="text-sm font-medium"
                                >
                                    Receipt reference
                                </label>
                                <Input
                                    id="receipt_reference"
                                    value={form.data.receipt_reference}
                                    onChange={(event) =>
                                        form.setData(
                                            'receipt_reference',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <label
                                    htmlFor="package_reference"
                                    className="text-sm font-medium"
                                >
                                    Package reference
                                </label>
                                <Input
                                    id="package_reference"
                                    value={form.data.package_reference}
                                    onChange={(event) =>
                                        form.setData(
                                            'package_reference',
                                            event.target.value,
                                        )
                                    }
                                />
                            </div>
                            <div className="space-y-2">
                                <label
                                    htmlFor="received_at"
                                    className="text-sm font-medium"
                                >
                                    Received at
                                </label>
                                <Input
                                    id="received_at"
                                    type="datetime-local"
                                    value={form.data.received_at}
                                    onChange={(event) =>
                                        form.setData(
                                            'received_at',
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
                                    Record Bid Receipt
                                </Button>
                            </div>
                        </form>
                    ) : (
                        <div className="bg-muted/40 rounded-xl border px-5 py-4 text-sm">
                            This round is historical or terminal. Bid receipt
                            and opening are read-only.
                        </div>
                    )}

                    <div className="space-y-4">
                        {round.bid_submissions.length === 0 ? (
                            <div className="bg-card flex min-h-56 flex-col items-center justify-center gap-2 rounded-xl border px-6 py-12 text-center shadow-xs">
                                <p className="font-medium">No bids received</p>
                                <p className="text-muted-foreground max-w-2xl text-sm">
                                    Record sealed submissions from registered
                                    bidders. Opening details remain separate
                                    from later eligibility and bid evaluation
                                    decisions.
                                </p>
                            </div>
                        ) : (
                            round.bid_submissions.map((submission) => (
                                <div
                                    key={submission.id}
                                    className="bg-card overflow-hidden rounded-xl border shadow-xs"
                                >
                                    <div className="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-6">
                                        <div className="xl:col-span-2">
                                            <p className="font-medium">
                                                {
                                                    submission
                                                        .bidder_participation
                                                        .supplier_name_snapshot
                                                }
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {
                                                    submission
                                                        .bidder_participation
                                                        .supplier_code_snapshot
                                                }
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground text-xs">
                                                Receipt
                                            </p>
                                            <p className="mt-1">
                                                {submission.receipt_reference ??
                                                    '—'}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground text-xs">
                                                Received
                                            </p>
                                            <p className="mt-1">
                                                {dateTime(
                                                    submission.received_at,
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground text-xs">
                                                Amount
                                            </p>
                                            <p className="mt-1">
                                                {money(
                                                    submission.bid_amount,
                                                    submission.currency,
                                                )}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-muted-foreground text-xs">
                                                Status
                                            </p>
                                            <div className="mt-1">
                                                <Badge variant="outline">
                                                    {humanize(
                                                        submission.status,
                                                    )}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>

                                    {submission.status === 'opened' ? (
                                        <div className="bg-muted/30 grid gap-3 border-t p-4 text-sm md:grid-cols-2 xl:grid-cols-4">
                                            <div>
                                                <span className="text-muted-foreground">
                                                    Opened:{' '}
                                                </span>
                                                {dateTime(
                                                    submission.opened_at,
                                                )}
                                            </div>
                                            <div>
                                                <span className="text-muted-foreground">
                                                    Eligibility docs:{' '}
                                                </span>
                                                {submission.eligibility_documents_present
                                                    ? 'Present'
                                                    : 'Not present'}
                                            </div>
                                            <div>
                                                <span className="text-muted-foreground">
                                                    Technical docs:{' '}
                                                </span>
                                                {submission.technical_documents_present
                                                    ? 'Present'
                                                    : 'Not present'}
                                            </div>
                                            <div>
                                                <span className="text-muted-foreground">
                                                    Financial docs:{' '}
                                                </span>
                                                {submission.financial_documents_present
                                                    ? 'Present'
                                                    : 'Not present'}
                                            </div>
                                            <div>
                                                <span className="text-muted-foreground">
                                                    Bid security:{' '}
                                                </span>
                                                {submission.bid_security_present
                                                    ? 'Present'
                                                    : 'Not present'}
                                            </div>
                                            {submission.opening_remarks && (
                                                <div className="md:col-span-2 xl:col-span-3">
                                                    <span className="text-muted-foreground">
                                                        Remarks:{' '}
                                                    </span>
                                                    {
                                                        submission.opening_remarks
                                                    }
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        canModify && (
                                            <OpeningForm
                                                projectId={project.id}
                                                roundId={round.id}
                                                submission={submission}
                                            />
                                        )
                                    )}
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
