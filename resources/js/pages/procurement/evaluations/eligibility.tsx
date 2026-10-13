import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ClipboardCheck } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type EvaluationItem = {
    id: number;
    sequence_no: number;
    criterion: string;
    result: string;
    remarks: string | null;
};

type Evaluation = {
    id: number;
    status: string;
    evaluated_at: string | null;
    decision_remarks: string | null;
    evaluator: { id: number; name: string } | null;
    items: EvaluationItem[];
};

type Submission = {
    id: number;
    bid_amount: string | null;
    currency: string;
    opened_at: string;
    bidder_participation: {
        id: number;
        supplier_code_snapshot: string;
        supplier_name_snapshot: string;
        philgeps_registration_no_snapshot: string | null;
    };
    eligibility_evaluation: Evaluation | null;
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

function money(value: string | null, currency: string) {
    if (value === null) {
        return '—';
    }

    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency,
    }).format(Number(value));
}

function CreateEvaluationForm({
    projectId,
    roundId,
    submission,
}: {
    projectId: number;
    roundId: number;
    submission: Submission;
}) {
    const form = useForm({
        bid_submission_id: submission.id,
        criteria_text: '',
    });

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.transform((data) => ({
            bid_submission_id: data.bid_submission_id,
            criteria: data.criteria_text
                .split('\n')
                .map((criterion) => criterion.trim())
                .filter(Boolean),
        })).post(
            `/procurement/projects/${projectId}/rounds/${roundId}/eligibility`,
            {
                preserveScroll: true,
                onFinish: () => form.transform((data) => data),
            },
        );
    };

    return (
        <form onSubmit={submit} className="space-y-3 border-t p-4">
            <div className="space-y-2">
                <label
                    htmlFor={`criteria-${submission.id}`}
                    className="text-sm font-medium"
                >
                    Eligibility checklist
                </label>
                <textarea
                    id={`criteria-${submission.id}`}
                    rows={5}
                    value={form.data.criteria_text}
                    onChange={(event) =>
                        form.setData('criteria_text', event.target.value)
                    }
                    className="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                    placeholder={'Enter one human-reviewed criterion per line'}
                />
                <p className="text-muted-foreground text-xs">
                    Criteria are recorded for human review. The system does not
                    determine eligibility automatically.
                </p>
            </div>
            <Button type="submit" size="sm" disabled={form.processing}>
                <ClipboardCheck />
                Create Evaluation Checklist
            </Button>
        </form>
    );
}

function FinalizeEvaluationForm({
    projectId,
    roundId,
    evaluation,
}: {
    projectId: number;
    roundId: number;
    evaluation: Evaluation;
}) {
    const form = useForm({
        status: 'eligible',
        decision_remarks: '',
        items: evaluation.items.map((item) => ({
            id: item.id,
            result: 'compliant',
            remarks: '',
        })),
    });

    const setItem = (
        index: number,
        field: 'result' | 'remarks',
        value: string,
    ) => {
        const items = [...form.data.items];
        items[index] = {
            ...items[index],
            [field]: value,
        };
        form.setData('items', items);
    };

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.patch(
            `/procurement/projects/${projectId}/rounds/${roundId}/eligibility/${evaluation.id}/finalize`,
            { preserveScroll: true },
        );
    };

    return (
        <form onSubmit={submit} className="space-y-4 border-t p-4">
            <div className="overflow-x-auto rounded-md border">
                <table className="w-full text-sm">
                    <thead className="bg-muted/50 text-left">
                        <tr className="border-b">
                            <th className="px-3 py-2 font-medium">#</th>
                            <th className="px-3 py-2 font-medium">Criterion</th>
                            <th className="px-3 py-2 font-medium">Result</th>
                            <th className="px-3 py-2 font-medium">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        {evaluation.items.map((item, index) => (
                            <tr
                                key={item.id}
                                className="border-b last:border-0"
                            >
                                <td className="px-3 py-2 align-top">
                                    {item.sequence_no}
                                </td>
                                <td className="max-w-xl px-3 py-2 align-top">
                                    {item.criterion}
                                </td>
                                <td className="px-3 py-2 align-top">
                                    <select
                                        value={form.data.items[index].result}
                                        onChange={(event) =>
                                            setItem(
                                                index,
                                                'result',
                                                event.target.value,
                                            )
                                        }
                                        className="border-input bg-background h-9 rounded-md border px-3 text-sm shadow-xs"
                                    >
                                        <option value="compliant">
                                            Compliant
                                        </option>
                                        <option value="non_compliant">
                                            Non-compliant
                                        </option>
                                        <option value="not_applicable">
                                            Not applicable
                                        </option>
                                    </select>
                                </td>
                                <td className="px-3 py-2 align-top">
                                    <input
                                        value={form.data.items[index].remarks}
                                        onChange={(event) =>
                                            setItem(
                                                index,
                                                'remarks',
                                                event.target.value,
                                            )
                                        }
                                        className="border-input bg-background h-9 min-w-64 rounded-md border px-3 text-sm shadow-xs"
                                        placeholder="Optional observation"
                                    />
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                    <label
                        htmlFor={`decision-${evaluation.id}`}
                        className="text-sm font-medium"
                    >
                        Human decision
                    </label>
                    <select
                        id={`decision-${evaluation.id}`}
                        value={form.data.status}
                        onChange={(event) =>
                            form.setData('status', event.target.value)
                        }
                        className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs"
                    >
                        <option value="eligible">Eligible</option>
                        <option value="ineligible">Ineligible</option>
                    </select>
                </div>
                <div className="space-y-2">
                    <label
                        htmlFor={`decision-remarks-${evaluation.id}`}
                        className="text-sm font-medium"
                    >
                        Decision remarks
                    </label>
                    <textarea
                        id={`decision-remarks-${evaluation.id}`}
                        rows={3}
                        value={form.data.decision_remarks}
                        onChange={(event) =>
                            form.setData('decision_remarks', event.target.value)
                        }
                        className="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                        placeholder="Required when recording an ineligible decision"
                    />
                </div>
            </div>

            <Button type="submit" size="sm" disabled={form.processing}>
                Finalize Eligibility Evaluation
            </Button>
        </form>
    );
}

export default function EligibilityEvaluationIndex({
    project,
    round,
    submissions,
    canModify,
}: {
    project: Project;
    round: Round;
    submissions: Submission[];
    canModify: boolean;
}) {
    return (
        <>
            <Head
                title={`Eligibility - ${project.reference_no} - Round ${round.round_no}`}
            />
            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <div className="space-y-3">
                        <Button asChild variant="ghost" size="sm">
                            <Link
                                href={`/procurement/projects/${project.id}/rounds/${round.id}/bids`}
                            >
                                <ArrowLeft />
                                Bid Receipt & Opening
                            </Link>
                        </Button>
                        <Heading
                            title={`${project.reference_no} — Eligibility Evaluation`}
                            description="Record the BAC evaluator's checklist and explicit eligibility decision. Checklist results support the decision but do not determine it automatically."
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

                    {submissions.length === 0 ? (
                        <div className="bg-card flex min-h-56 flex-col items-center justify-center gap-2 rounded-xl border px-6 py-12 text-center shadow-xs">
                            <p className="font-medium">
                                No opened bids available for evaluation
                            </p>
                            <p className="text-muted-foreground max-w-2xl text-sm">
                                Eligibility evaluation becomes available after a
                                bid has been formally opened.
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {submissions.map((submission) => {
                                const evaluation =
                                    submission.eligibility_evaluation;

                                return (
                                    <div
                                        key={submission.id}
                                        className="bg-card overflow-hidden rounded-xl border shadow-xs"
                                    >
                                        <div className="grid gap-4 p-4 md:grid-cols-3">
                                            <div>
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
                                                    Opened bid
                                                </p>
                                                <p className="mt-1 font-medium">
                                                    {money(
                                                        submission.bid_amount,
                                                        submission.currency,
                                                    )}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-muted-foreground text-xs">
                                                    Eligibility status
                                                </p>
                                                <div className="mt-1">
                                                    <Badge variant="outline">
                                                        {evaluation
                                                            ? humanize(
                                                                  evaluation.status,
                                                              )
                                                            : 'Not started'}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>

                                        {!evaluation && canModify && (
                                            <CreateEvaluationForm
                                                projectId={project.id}
                                                roundId={round.id}
                                                submission={submission}
                                            />
                                        )}

                                        {evaluation?.status === 'pending' &&
                                            canModify && (
                                                <FinalizeEvaluationForm
                                                    projectId={project.id}
                                                    roundId={round.id}
                                                    evaluation={evaluation}
                                                />
                                            )}

                                        {evaluation &&
                                            evaluation.status !== 'pending' && (
                                                <div className="space-y-3 border-t p-4">
                                                    <div className="text-sm">
                                                        <span className="text-muted-foreground">
                                                            Evaluator:{' '}
                                                        </span>
                                                        {evaluation.evaluator
                                                            ?.name ?? '—'}
                                                    </div>
                                                    <div className="overflow-x-auto rounded-md border">
                                                        <table className="w-full text-sm">
                                                            <thead className="bg-muted/50 text-left">
                                                                <tr className="border-b">
                                                                    <th className="px-3 py-2 font-medium">
                                                                        #
                                                                    </th>
                                                                    <th className="px-3 py-2 font-medium">
                                                                        Criterion
                                                                    </th>
                                                                    <th className="px-3 py-2 font-medium">
                                                                        Result
                                                                    </th>
                                                                    <th className="px-3 py-2 font-medium">
                                                                        Remarks
                                                                    </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                {evaluation.items.map(
                                                                    (item) => (
                                                                        <tr
                                                                            key={
                                                                                item.id
                                                                            }
                                                                            className="border-b last:border-0"
                                                                        >
                                                                            <td className="px-3 py-2">
                                                                                {
                                                                                    item.sequence_no
                                                                                }
                                                                            </td>
                                                                            <td className="px-3 py-2">
                                                                                {
                                                                                    item.criterion
                                                                                }
                                                                            </td>
                                                                            <td className="px-3 py-2">
                                                                                {humanize(
                                                                                    item.result,
                                                                                )}
                                                                            </td>
                                                                            <td className="px-3 py-2">
                                                                                {item.remarks ??
                                                                                    '—'}
                                                                            </td>
                                                                        </tr>
                                                                    ),
                                                                )}
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    {evaluation.decision_remarks && (
                                                        <p className="text-sm">
                                                            <span className="text-muted-foreground">
                                                                Decision
                                                                remarks:{' '}
                                                            </span>
                                                            {
                                                                evaluation.decision_remarks
                                                            }
                                                        </p>
                                                    )}
                                                </div>
                                            )}
                                    </div>
                                );
                            })}
                        </div>
                    )}

                    {!canModify && (
                        <div className="bg-muted/40 rounded-xl border px-5 py-4 text-sm">
                            This round is historical or terminal. Eligibility
                            evaluations are read-only.
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
