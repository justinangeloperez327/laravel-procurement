import { Form, Link } from '@inertiajs/react';
import MarketScopingController from '@/actions/App/Http/Controllers/Planning/MarketScopingController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type Option = {
    id: number;
    year?: number;
    code?: string;
    name?: string;
};

type Category = {
    value: string;
    label: string;
};

type MarketScopingDraft = {
    id: number;
    reference_no: string;
    fiscal_year_id: number;
    organizational_unit_id: number;
    title: string;
    procurement_category: string;
    description: string | null;
    market_findings: string | null;
    recommended_strategy: string | null;
};

export default function MarketScopingForm({
    fiscalYears,
    organizationalUnits,
    categories,
    marketScoping,
}: {
    fiscalYears: Option[];
    organizationalUnits: Option[];
    categories: Category[];
    marketScoping?: MarketScopingDraft;
}) {
    const form = marketScoping
        ? MarketScopingController.update.form(marketScoping.id)
        : MarketScopingController.store.form();

    return (
        <Form {...form} className="space-y-6">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-6 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="reference_no">Reference No.</Label>
                            <Input
                                id="reference_no"
                                name="reference_no"
                                defaultValue={marketScoping?.reference_no}
                                placeholder="MS-2027-001"
                                required
                            />
                            <InputError message={errors.reference_no} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="title">Procurement Title</Label>
                            <Input
                                id="title"
                                name="title"
                                defaultValue={marketScoping?.title}
                                placeholder="Supply and delivery of..."
                                required
                            />
                            <InputError message={errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="fiscal_year_id">Fiscal Year</Label>
                            <Select
                                name="fiscal_year_id"
                                defaultValue={
                                    marketScoping
                                        ? String(marketScoping.fiscal_year_id)
                                        : undefined
                                }
                                required
                            >
                                <SelectTrigger
                                    id="fiscal_year_id"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select fiscal year" />
                                </SelectTrigger>
                                <SelectContent>
                                    {fiscalYears.map((fiscalYear) => (
                                        <SelectItem
                                            key={fiscalYear.id}
                                            value={String(fiscalYear.id)}
                                        >
                                            {fiscalYear.year}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.fiscal_year_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="organizational_unit_id">
                                Organizational Unit
                            </Label>
                            <Select
                                name="organizational_unit_id"
                                defaultValue={
                                    marketScoping
                                        ? String(
                                              marketScoping.organizational_unit_id,
                                          )
                                        : undefined
                                }
                                required
                            >
                                <SelectTrigger
                                    id="organizational_unit_id"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select unit" />
                                </SelectTrigger>
                                <SelectContent>
                                    {organizationalUnits.map((unit) => (
                                        <SelectItem
                                            key={unit.id}
                                            value={String(unit.id)}
                                        >
                                            {unit.code
                                                ? `${unit.code} — ${unit.name}`
                                                : unit.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={errors.organizational_unit_id}
                            />
                        </div>

                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="procurement_category">
                                Procurement Category
                            </Label>
                            <Select
                                name="procurement_category"
                                defaultValue={
                                    marketScoping?.procurement_category
                                }
                                required
                            >
                                <SelectTrigger
                                    id="procurement_category"
                                    className="w-full"
                                >
                                    <SelectValue placeholder="Select category" />
                                </SelectTrigger>
                                <SelectContent>
                                    {categories.map((category) => (
                                        <SelectItem
                                            key={category.value}
                                            value={category.value}
                                        >
                                            {category.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.procurement_category} />
                        </div>
                    </div>

                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="description">
                                Requirement Description
                            </Label>
                            <textarea
                                id="description"
                                name="description"
                                defaultValue={marketScoping?.description ?? ''}
                                rows={4}
                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                placeholder="Describe the requirement, intended outcome, and key specifications."
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="market_findings">
                                Market Findings
                            </Label>
                            <textarea
                                id="market_findings"
                                name="market_findings"
                                defaultValue={
                                    marketScoping?.market_findings ?? ''
                                }
                                rows={5}
                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                placeholder="Summarize supplier availability, indicative pricing, market capacity, risks, and other relevant findings."
                            />
                            <InputError message={errors.market_findings} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="recommended_strategy">
                                Recommended Procurement Strategy
                            </Label>
                            <textarea
                                id="recommended_strategy"
                                name="recommended_strategy"
                                defaultValue={
                                    marketScoping?.recommended_strategy ?? ''
                                }
                                rows={5}
                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive min-h-28 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                placeholder="Record the recommended approach based on the market findings."
                            />
                            <InputError message={errors.recommended_strategy} />
                        </div>
                    </div>

                    <div className="flex items-center justify-end gap-3 border-t pt-6">
                        <Button variant="outline" asChild>
                            <Link href={MarketScopingController.index()}>
                                Cancel
                            </Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {marketScoping ? 'Update draft' : 'Save draft'}
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}
