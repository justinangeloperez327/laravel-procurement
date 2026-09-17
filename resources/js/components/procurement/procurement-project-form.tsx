import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type AppItem = {
    id: number;
    app_item_no: string;
    title: string;
    description: string | null;
    procurement_category: string;
    estimated_budget: string;
    remaining_budget: string;
    funding_source: string | null;
    is_early_procurement_activity: boolean;
    procurement_method: {
        id: number;
        code: string;
        name: string;
    };
    annual_procurement_plan: {
        id: number;
        reference_no: string;
        version: number;
        app_type: string;
    };
};

type Officer = {
    id: number;
    name: string;
    position_title: string | null;
};

type Project = {
    id: number;
    app_item_id: number;
    reference_no: string;
    title: string;
    description: string | null;
    approved_budget: string;
    procurement_officer_id: number | null;
    target_start_date: string | null;
    target_completion_date: string | null;
    app_item: AppItem;
};

type Props = {
    appItems?: AppItem[];
    procurementOfficers: Officer[];
    project?: Project;
    remainingBudget?: string;
};

function money(value: string) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value));
}

function dateValue(value: string | null | undefined) {
    return value ? value.slice(0, 10) : '';
}

export default function ProcurementProjectForm({
    appItems = [],
    procurementOfficers,
    project,
    remainingBudget,
}: Props) {
    const editing = Boolean(project);
    const initialSource = project?.app_item ?? null;
    const { data, setData, post, put, processing, errors } = useForm({
        app_item_id: project?.app_item_id?.toString() ?? '',
        reference_no: project?.reference_no ?? '',
        title: project?.title ?? '',
        description: project?.description ?? '',
        approved_budget: project?.approved_budget ?? '',
        procurement_officer_id:
            project?.procurement_officer_id?.toString() ?? '',
        target_start_date: dateValue(project?.target_start_date),
        target_completion_date: dateValue(project?.target_completion_date),
    });

    const selectedSource =
        initialSource ??
        appItems.find((item) => item.id.toString() === data.app_item_id) ??
        null;
    const availableBudget = editing
        ? (remainingBudget ?? project?.approved_budget ?? '0')
        : (selectedSource?.remaining_budget ?? '0');

    function selectSource(value: string) {
        const source = appItems.find((item) => item.id.toString() === value);

        setData((current) => ({
            ...current,
            app_item_id: value,
            title: source?.title ?? '',
            description: source?.description ?? '',
            approved_budget: source?.remaining_budget ?? '',
        }));
    }

    function submit(event: React.FormEvent) {
        event.preventDefault();

        if (editing && project) {
            put(`/procurement/projects/${project.id}`);
            return;
        }

        post('/procurement/projects');
    }

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid gap-6 lg:grid-cols-2">
                <div className="space-y-2 lg:col-span-2">
                    <Label htmlFor="app_item_id">Approved APP Item</Label>
                    {editing && selectedSource ? (
                        <div className="bg-muted/40 rounded-lg border p-4 text-sm">
                            <div className="font-medium">
                                {
                                    selectedSource.annual_procurement_plan
                                        .reference_no
                                }{' '}
                                v
                                {selectedSource.annual_procurement_plan.version}{' '}
                                · Item {selectedSource.app_item_no}
                            </div>
                            <div className="text-muted-foreground mt-1">
                                {selectedSource.title}
                            </div>
                        </div>
                    ) : (
                        <select
                            id="app_item_id"
                            value={data.app_item_id}
                            onChange={(event) =>
                                selectSource(event.target.value)
                            }
                            className="border-input bg-background h-10 w-full rounded-md border px-3 text-sm"
                            required
                        >
                            <option value="">
                                Select an approved APP item
                            </option>
                            {appItems.map((item) => (
                                <option key={item.id} value={item.id}>
                                    {item.annual_procurement_plan.reference_no}{' '}
                                    v{item.annual_procurement_plan.version} ·
                                    Item {item.app_item_no} · {item.title} ·
                                    Remaining {money(item.remaining_budget)}
                                </option>
                            ))}
                        </select>
                    )}
                    <InputError message={errors.app_item_id} />
                </div>

                {selectedSource && (
                    <div className="bg-muted/40 grid gap-3 rounded-lg border p-4 text-sm md:grid-cols-4 lg:col-span-2">
                        <div>
                            <div className="text-muted-foreground">
                                APP Type
                            </div>
                            <div className="font-medium capitalize">
                                {
                                    selectedSource.annual_procurement_plan
                                        .app_type
                                }
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">Method</div>
                            <div className="font-medium">
                                {selectedSource.procurement_method.name}
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">Funding</div>
                            <div className="font-medium">
                                {selectedSource.funding_source || '—'}
                            </div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">
                                Available Budget
                            </div>
                            <div className="font-medium">
                                {money(availableBudget)}
                            </div>
                        </div>
                    </div>
                )}

                <div className="space-y-2">
                    <Label htmlFor="reference_no">Project Reference</Label>
                    <Input
                        id="reference_no"
                        value={data.reference_no}
                        onChange={(event) =>
                            setData('reference_no', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.reference_no} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="approved_budget">Allocated Budget</Label>
                    <Input
                        id="approved_budget"
                        type="number"
                        min="0.01"
                        step="0.01"
                        value={data.approved_budget}
                        onChange={(event) =>
                            setData('approved_budget', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.approved_budget} />
                </div>

                <div className="space-y-2 lg:col-span-2">
                    <Label htmlFor="title">Project Title</Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(event) =>
                            setData('title', event.target.value)
                        }
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div className="space-y-2 lg:col-span-2">
                    <Label htmlFor="description">Description / Scope</Label>
                    <textarea
                        id="description"
                        rows={4}
                        value={data.description}
                        onChange={(event) =>
                            setData('description', event.target.value)
                        }
                        className="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
                    />
                    <InputError message={errors.description} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="procurement_officer_id">
                        Procurement Officer
                    </Label>
                    <select
                        id="procurement_officer_id"
                        value={data.procurement_officer_id}
                        onChange={(event) =>
                            setData(
                                'procurement_officer_id',
                                event.target.value,
                            )
                        }
                        className="border-input bg-background h-10 w-full rounded-md border px-3 text-sm"
                    >
                        <option value="">Unassigned</option>
                        {procurementOfficers.map((officer) => (
                            <option key={officer.id} value={officer.id}>
                                {officer.name}
                                {officer.position_title
                                    ? ` — ${officer.position_title}`
                                    : ''}
                            </option>
                        ))}
                    </select>
                    <InputError message={errors.procurement_officer_id} />
                </div>

                <div />

                <div className="space-y-2">
                    <Label htmlFor="target_start_date">Target Start</Label>
                    <Input
                        id="target_start_date"
                        type="date"
                        value={data.target_start_date}
                        onChange={(event) =>
                            setData('target_start_date', event.target.value)
                        }
                    />
                    <InputError message={errors.target_start_date} />
                </div>

                <div className="space-y-2">
                    <Label htmlFor="target_completion_date">
                        Target Completion
                    </Label>
                    <Input
                        id="target_completion_date"
                        type="date"
                        value={data.target_completion_date}
                        onChange={(event) =>
                            setData(
                                'target_completion_date',
                                event.target.value,
                            )
                        }
                    />
                    <InputError message={errors.target_completion_date} />
                </div>
            </div>

            <div className="flex justify-end">
                <Button type="submit" disabled={processing || !selectedSource}>
                    {editing ? 'Save Changes' : 'Initiate Procurement Project'}
                </Button>
            </div>
        </form>
    );
}
