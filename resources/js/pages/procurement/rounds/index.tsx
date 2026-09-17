import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus } from 'lucide-react';
import ProcurementProjectController from '@/actions/App/Http/Controllers/Procurement/ProcurementProjectController';
import ProcurementRoundController from '@/actions/App/Http/Controllers/Procurement/ProcurementRoundController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

type Round = {
    id: number;
    round_no: number;
    reference_no: string | null;
    status: string;
    failure_reason: string | null;
    pre_procurement_at: string | null;
    posting_started_at: string | null;
    bid_opening_at: string | null;
    procurement_method: { id: number; code: string; name: string };
    creator: { id: number; name: string };
};

type Project = {
    id: number;
    reference_no: string;
    title: string;
    status: string;
    current_stage: string | null;
    procurement_method: { id: number; code: string; name: string };
    rounds: Round[];
};

function humanize(value: string) {
    return value
        .split('_')
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
}

export default function ProcurementRoundsIndex({
    project,
    canStartRound,
}: {
    project: Project;
    canStartRound: boolean;
}) {
    const startRound = () => {
        router.post(ProcurementRoundController.store(project.id).url, {});
    };

    return (
        <>
            <Head title={`Procurement Rounds - ${project.reference_no}`} />
            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-6xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div className="space-y-3">
                            <Button asChild variant="ghost" size="sm">
                                <Link href={ProcurementProjectController.index()}>
                                    <ArrowLeft />
                                    Procurement Projects
                                </Link>
                            </Button>
                            <Heading
                                title={`${project.reference_no} — Procurement Rounds`}
                                description={project.title}
                            />
                        </div>
                        {canStartRound && (
                            <Button onClick={startRound}>
                                <Plus />
                                Start Procurement Round
                            </Button>
                        )}
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="bg-card rounded-xl border p-4 shadow-xs">
                            <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                                Project Status
                            </p>
                            <p className="mt-2 font-semibold">{humanize(project.status)}</p>
                        </div>
                        <div className="bg-card rounded-xl border p-4 shadow-xs">
                            <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                                Current Stage
                            </p>
                            <p className="mt-2 font-semibold">
                                {project.current_stage ? humanize(project.current_stage) : 'Not started'}
                            </p>
                        </div>
                        <div className="bg-card rounded-xl border p-4 shadow-xs">
                            <p className="text-muted-foreground text-xs font-medium uppercase tracking-wide">
                                Procurement Method
                            </p>
                            <p className="mt-2 font-semibold">{project.procurement_method.name}</p>
                        </div>
                    </div>

                    <div className="bg-card overflow-hidden rounded-xl border shadow-xs">
                        {project.rounds.length === 0 ? (
                            <div className="flex min-h-56 flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                                <p className="font-medium">No procurement rounds yet</p>
                                <p className="text-muted-foreground max-w-xl text-sm">
                                    Start the first round to begin procurement preparation. Later rebids will be retained as separate rounds instead of overwriting prior history.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 text-muted-foreground">
                                        <tr className="border-b text-left">
                                            <th className="px-4 py-3 font-medium">Round</th>
                                            <th className="px-4 py-3 font-medium">Method</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium">Created By</th>
                                            <th className="px-4 py-3 font-medium">Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {project.rounds.map((round) => (
                                            <tr key={round.id} className="border-b last:border-0">
                                                <td className="px-4 py-3 font-medium">#{round.round_no}</td>
                                                <td className="px-4 py-3">{round.procurement_method.name}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="outline">{humanize(round.status)}</Badge>
                                                </td>
                                                <td className="px-4 py-3">{round.creator.name}</td>
                                                <td className="px-4 py-3">{round.reference_no ?? '—'}</td>
                                            </tr>
                                        ))}
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
