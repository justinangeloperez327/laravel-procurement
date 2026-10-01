import { Head, Link, router, useForm } from "@inertiajs/react";
import { ArrowLeft, Check, Plus } from "lucide-react";
import Heading from "@/components/heading";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

type UserOption = {
    id: number;
    name: string;
};

type ActivityType = {
    value: string;
    label: string;
};

type Activity = {
    id: number;
    sequence_no: number;
    activity_type: string;
    status: string;
    scheduled_at: string | null;
    actual_at: string | null;
    responsible_user: UserOption | null;
    creator: UserOption;
    minutes: string | null;
    remarks: string | null;
};

type Round = {
    id: number;
    round_no: number;
    reference_no: string | null;
    status: string;
    procurement_method: {
        id: number;
        code: string;
        name: string;
    };
    activities: Activity[];
};

type Project = {
    id: number;
    reference_no: string;
    title: string;
    status: string;
    current_stage: string | null;
};

function humanize(value: string) {
    return value
        .split("_")
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(" ");
}

function dateTime(value: string | null) {
    if (!value) {
        return "—";
    }

    return new Intl.DateTimeFormat("en-PH", {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(value));
}

export default function ProcurementActivitiesIndex({
    project,
    round,
    activityTypes,
    responsibleUsers,
    canModify,
}: {
    project: Project;
    round: Round;
    activityTypes: ActivityType[];
    responsibleUsers: UserOption[];
    canModify: boolean;
}) {
    const form = useForm({
        activity_type: activityTypes[0]?.value ?? "",
        scheduled_at: "",
        responsible_user_id: "",
        remarks: "",
    });

    const baseUrl = `/procurement/projects/${project.id}/rounds/${round.id}/activities`;

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(baseUrl, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset("scheduled_at", "responsible_user_id", "remarks");
            },
        });
    };

    const completeActivity = (activity: Activity) => {
        router.patch(`${baseUrl}/${activity.id}/complete`, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={`Activities - ${project.reference_no} - Round ${round.round_no}`} />

            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <div className="space-y-3">
                        <Button asChild variant="ghost" size="sm">
                            <Link href={`/procurement/projects/${project.id}/rounds`}>
                                <ArrowLeft />
                                Procurement Rounds
                            </Link>
                        </Button>
                        <Heading
                            title={`${project.reference_no} — Round #${round.round_no}`}
                            description="Plan and record the procurement activities for this round without overwriting historical execution evidence."
                        />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="bg-card rounded-xl border p-4 shadow-xs">
                            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                Project
                            </p>
                            <p className="mt-2 font-semibold">{project.title}</p>
                        </div>
                        <div className="bg-card rounded-xl border p-4 shadow-xs">
                            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                Procurement Method
                            </p>
                            <p className="mt-2 font-semibold">{round.procurement_method.name}</p>
                        </div>
                        <div className="bg-card rounded-xl border p-4 shadow-xs">
                            <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">
                                Round Status
                            </p>
                            <div className="mt-2">
                                <Badge variant="outline">{humanize(round.status)}</Badge>
                            </div>
                        </div>
                    </div>

                    {canModify ? (
                        <form
                            onSubmit={submit}
                            className="bg-card grid gap-4 rounded-xl border p-5 shadow-xs md:grid-cols-2 md:p-6 xl:grid-cols-4"
                        >
                            <div className="space-y-2">
                                <label htmlFor="activity_type" className="text-sm font-medium">
                                    Activity
                                </label>
                                <select
                                    id="activity_type"
                                    value={form.data.activity_type}
                                    onChange={(event) =>
                                        form.setData("activity_type", event.target.value)
                                    }
                                    className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs"
                                >
                                    {activityTypes.map((type) => (
                                        <option key={type.value} value={type.value}>
                                            {type.label}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.activity_type && (
                                    <p className="text-destructive text-xs">
                                        {form.errors.activity_type}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label htmlFor="scheduled_at" className="text-sm font-medium">
                                    Scheduled date
                                </label>
                                <Input
                                    id="scheduled_at"
                                    type="datetime-local"
                                    value={form.data.scheduled_at}
                                    onChange={(event) =>
                                        form.setData("scheduled_at", event.target.value)
                                    }
                                />
                                {form.errors.scheduled_at && (
                                    <p className="text-destructive text-xs">
                                        {form.errors.scheduled_at}
                                    </p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <label
                                    htmlFor="responsible_user_id"
                                    className="text-sm font-medium"
                                >
                                    Responsible user
                                </label>
                                <select
                                    id="responsible_user_id"
                                    value={form.data.responsible_user_id}
                                    onChange={(event) =>
                                        form.setData("responsible_user_id", event.target.value)
                                    }
                                    className="border-input bg-background h-9 w-full rounded-md border px-3 text-sm shadow-xs"
                                >
                                    <option value="">Unassigned</option>
                                    {responsibleUsers.map((user) => (
                                        <option key={user.id} value={user.id}>
                                            {user.name}
                                        </option>
                                    ))}
                                </select>
                                {form.errors.responsible_user_id && (
                                    <p className="text-destructive text-xs">
                                        {form.errors.responsible_user_id}
                                    </p>
                                )}
                            </div>

                            <div className="flex items-end">
                                <Button type="submit" className="w-full" disabled={form.processing}>
                                    <Plus />
                                    Add Activity
                                </Button>
                            </div>

                            <div className="space-y-2 md:col-span-2 xl:col-span-4">
                                <label htmlFor="remarks" className="text-sm font-medium">
                                    Remarks
                                </label>
                                <textarea
                                    id="remarks"
                                    value={form.data.remarks}
                                    onChange={(event) =>
                                        form.setData("remarks", event.target.value)
                                    }
                                    rows={3}
                                    className="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                    placeholder="Optional scheduling context or instructions"
                                />
                                {form.errors.remarks && (
                                    <p className="text-destructive text-xs">
                                        {form.errors.remarks}
                                    </p>
                                )}
                            </div>
                        </form>
                    ) : (
                        <div className="bg-muted/40 rounded-xl border px-5 py-4 text-sm">
                            This round is historical or terminal. Its activity record is read-only.
                        </div>
                    )}

                    <div className="bg-card overflow-hidden rounded-xl border shadow-xs">
                        {round.activities.length === 0 ? (
                            <div className="flex min-h-56 flex-col items-center justify-center gap-2 px-6 py-12 text-center">
                                <p className="font-medium">No procurement activities recorded</p>
                                <p className="text-muted-foreground max-w-2xl text-sm">
                                    Add the activities required for this procurement method. The
                                    system records the actual execution evidence without forcing
                                    every procurement method through one fixed sequence.
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 text-left">
                                        <tr className="border-b">
                                            <th className="px-4 py-3 font-medium">#</th>
                                            <th className="px-4 py-3 font-medium">Activity</th>
                                            <th className="px-4 py-3 font-medium">Scheduled</th>
                                            <th className="px-4 py-3 font-medium">Actual</th>
                                            <th className="px-4 py-3 font-medium">Responsible</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium">Remarks</th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {round.activities.map((activity) => (
                                            <tr
                                                key={activity.id}
                                                className="border-b align-top last:border-0"
                                            >
                                                <td className="px-4 py-3 font-medium tabular-nums">
                                                    {activity.sequence_no}
                                                </td>
                                                <td className="px-4 py-3 font-medium">
                                                    {humanize(activity.activity_type)}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    {dateTime(activity.scheduled_at)}
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    {dateTime(activity.actual_at)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {activity.responsible_user?.name ??
                                                        "Unassigned"}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="outline">
                                                        {humanize(activity.status)}
                                                    </Badge>
                                                </td>
                                                <td className="text-muted-foreground max-w-xs px-4 py-3">
                                                    {activity.remarks ?? "—"}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {canModify &&
                                                        activity.status !== "completed" &&
                                                        activity.status !== "cancelled" && (
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    completeActivity(activity)
                                                                }
                                                            >
                                                                <Check />
                                                                Complete
                                                            </Button>
                                                        )}
                                                </td>
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
