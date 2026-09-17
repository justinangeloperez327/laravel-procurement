import { Head, Link } from "@inertiajs/react";
import { Pencil, Plus } from "lucide-react";
import ProcurementProjectController from "@/actions/App/Http/Controllers/Procurement/ProcurementProjectController";
import Heading from "@/components/heading";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

type Project = {
    id: number;
    reference_no: string;
    title: string;
    approved_budget: string;
    status: string;
    current_stage: string | null;
    target_start_date: string | null;
    target_completion_date: string | null;
    fiscal_year: { id: number; year: number };
    procurement_method: { id: number; code: string; name: string };
    procurement_officer: { id: number; name: string } | null;
    app_item: {
        id: number;
        app_item_no: string;
        title: string;
        annual_procurement_plan: {
            id: number;
            reference_no: string;
            version: number;
            app_type: string;
        };
    };
};

type PaginatedProjects = {
    data: Project[];
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

function currency(value: string) {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(value));
}

function humanize(value: string) {
    return value
        .split("_")
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(" ");
}

export default function ProcurementProjectIndex({ projects }: { projects: PaginatedProjects }) {
    return (
        <>
            <Head title="Procurement Projects" />
            <div className="flex flex-1 flex-col p-4 md:p-6">
                <div className="mx-auto w-full max-w-7xl space-y-6">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <Heading
                            title="Procurement Projects"
                            description="Initiate and track procurement transactions from approved Annual Procurement Plan items."
                        />
                        <Button asChild>
                            <Link href={ProcurementProjectController.create()}>
                                <Plus />
                                Initiate Project
                            </Link>
                        </Button>
                    </div>

                    <div className="bg-card overflow-hidden rounded-xl border shadow-xs">
                        {projects.data.length === 0 ? (
                            <div className="flex min-h-64 flex-col items-center justify-center gap-3 px-6 py-12 text-center">
                                <div>
                                    <p className="font-medium">No procurement projects yet</p>
                                    <p className="text-muted-foreground mt-1 text-sm">
                                        Initiate a project from an eligible approved APP item.
                                    </p>
                                </div>
                                <Button asChild variant="outline">
                                    <Link href={ProcurementProjectController.create()}>
                                        <Plus />
                                        Initiate Project
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="bg-muted/50 text-left">
                                        <tr className="border-b">
                                            <th className="px-4 py-3 font-medium">Reference</th>
                                            <th className="px-4 py-3 font-medium">Project</th>
                                            <th className="px-4 py-3 font-medium">APP Source</th>
                                            <th className="px-4 py-3 font-medium">Method</th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Budget
                                            </th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium">Officer</th>
                                            <th className="px-4 py-3 text-right font-medium">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {projects.data.map((project) => (
                                            <tr key={project.id} className="border-b last:border-0">
                                                <td className="px-4 py-3 font-medium whitespace-nowrap">
                                                    {project.reference_no}
                                                </td>
                                                <td className="max-w-sm px-4 py-3">
                                                    <div className="truncate">{project.title}</div>
                                                    <div className="text-muted-foreground mt-1 text-xs">
                                                        FY {project.fiscal_year.year}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    <div>
                                                        {
                                                            project.app_item.annual_procurement_plan
                                                                .reference_no
                                                        }{" "}
                                                        v
                                                        {
                                                            project.app_item.annual_procurement_plan
                                                                .version
                                                        }
                                                    </div>
                                                    <div className="text-muted-foreground text-xs">
                                                        Item {project.app_item.app_item_no}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {project.procurement_method.name}
                                                </td>
                                                <td className="px-4 py-3 text-right font-medium whitespace-nowrap tabular-nums">
                                                    {currency(project.approved_budget)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="secondary">
                                                        {humanize(project.status)}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {project.procurement_officer?.name ??
                                                        "Unassigned"}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {project.status === "planned" && (
                                                        <Button asChild variant="outline" size="sm">
                                                            <Link
                                                                href={ProcurementProjectController.edit(
                                                                    project.id,
                                                                )}
                                                            >
                                                                <Pencil />
                                                                Edit
                                                            </Link>
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

                    {projects.total > 0 && (
                        <div className="flex items-center justify-between text-sm">
                            <div className="text-muted-foreground">
                                Showing {projects.from}–{projects.to} of {projects.total}
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    asChild={Boolean(projects.prev_page_url)}
                                    variant="outline"
                                    size="sm"
                                    disabled={!projects.prev_page_url}
                                >
                                    {projects.prev_page_url ? (
                                        <Link href={projects.prev_page_url}>Previous</Link>
                                    ) : (
                                        <span>Previous</span>
                                    )}
                                </Button>
                                <Button
                                    asChild={Boolean(projects.next_page_url)}
                                    variant="outline"
                                    size="sm"
                                    disabled={!projects.next_page_url}
                                >
                                    {projects.next_page_url ? (
                                        <Link href={projects.next_page_url}>Next</Link>
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
