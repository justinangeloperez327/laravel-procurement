import { Form, Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import PpmpController from '@/actions/App/Http/Controllers/Planning/PpmpController';
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

type FiscalYear = {
    id: number;
    year: number;
};

type OrganizationalUnit = {
    id: number;
    code: string | null;
    name: string;
};

type MarketScoping = {
    id: number;
    reference_no: string;
    title: string;
    fiscal_year_id: number;
};

type ProcurementMethod = {
    id: number;
    code: string;
    name: string;
};

type Category = {
    value: string;
    label: string;
};

type StoredPpmpItem = {
    id: number;
    market_scoping_id: number | null;
    recommended_procurement_method_id: number | null;
    item_no: string;
    title: string;
    description: string | null;
    procurement_category: string;
    quantity: string;
    unit: string;
    estimated_unit_cost: string | null;
    estimated_budget: string;
    funding_source: string | null;
    target_quarter: number | null;
    remarks: string | null;
};

type PpmpDraft = {
    id: number;
    reference_no: string;
    fiscal_year_id: number;
    organizational_unit_id: number;
    title: string;
    version: number;
    items: StoredPpmpItem[];
};

type FormItem = {
    key: string;
    market_scoping_id: string;
    recommended_procurement_method_id: string;
    item_no: string;
    title: string;
    description: string;
    procurement_category: string;
    quantity: string;
    unit: string;
    estimated_unit_cost: string;
    estimated_budget: string;
    funding_source: string;
    target_quarter: string;
    remarks: string;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

const textareaClassName =
    'border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

function emptyItem(index: number): FormItem {
    return {
        key: `new-${index}-${Date.now()}`,
        market_scoping_id: '',
        recommended_procurement_method_id: '',
        item_no: String(index + 1),
        title: '',
        description: '',
        procurement_category: '',
        quantity: '1',
        unit: '',
        estimated_unit_cost: '',
        estimated_budget: '0.00',
        funding_source: '',
        target_quarter: '',
        remarks: '',
    };
}

function storedItemToFormItem(item: StoredPpmpItem): FormItem {
    return {
        key: `stored-${item.id}`,
        market_scoping_id: item.market_scoping_id
            ? String(item.market_scoping_id)
            : '',
        recommended_procurement_method_id:
            item.recommended_procurement_method_id
                ? String(item.recommended_procurement_method_id)
                : '',
        item_no: item.item_no,
        title: item.title,
        description: item.description ?? '',
        procurement_category: item.procurement_category,
        quantity: item.quantity,
        unit: item.unit,
        estimated_unit_cost: item.estimated_unit_cost ?? '',
        estimated_budget: item.estimated_budget,
        funding_source: item.funding_source ?? '',
        target_quarter: item.target_quarter ? String(item.target_quarter) : '',
        remarks: item.remarks ?? '',
    };
}

function calculatedBudget(quantity: string, unitCost: string): string | null {
    if (quantity === '' || unitCost === '') {
        return null;
    }

    const parsedQuantity = Number(quantity);
    const parsedUnitCost = Number(unitCost);

    if (!Number.isFinite(parsedQuantity) || !Number.isFinite(parsedUnitCost)) {
        return null;
    }

    return (parsedQuantity * parsedUnitCost).toFixed(2);
}

export default function PpmpForm({
    fiscalYears,
    organizationalUnits,
    marketScopings,
    procurementMethods,
    categories,
    ppmp,
}: {
    fiscalYears: FiscalYear[];
    organizationalUnits: OrganizationalUnit[];
    marketScopings: MarketScoping[];
    procurementMethods: ProcurementMethod[];
    categories: Category[];
    ppmp?: PpmpDraft;
}) {
    const [fiscalYearId, setFiscalYearId] = useState(
        ppmp ? String(ppmp.fiscal_year_id) : '',
    );
    const [items, setItems] = useState<FormItem[]>(() =>
        ppmp?.items.length
            ? ppmp.items.map(storedItemToFormItem)
            : [emptyItem(0)],
    );

    const form = ppmp
        ? PpmpController.update.form(ppmp.id)
        : PpmpController.store.form();

    const availableMarketScopings = useMemo(
        () =>
            marketScopings.filter(
                (marketScoping) =>
                    fiscalYearId === '' ||
                    String(marketScoping.fiscal_year_id) === fiscalYearId,
            ),
        [fiscalYearId, marketScopings],
    );

    const totalBudget = useMemo(
        () =>
            items.reduce((total, item) => {
                const budget = Number(item.estimated_budget);
                return total + (Number.isFinite(budget) ? budget : 0);
            }, 0),
        [items],
    );

    function updateItem(
        index: number,
        field: keyof Omit<FormItem, 'key'>,
        value: string,
    ) {
        setItems((currentItems) =>
            currentItems.map((item, itemIndex) => {
                if (itemIndex !== index) {
                    return item;
                }

                const updatedItem = { ...item, [field]: value };

                if (field === 'quantity' || field === 'estimated_unit_cost') {
                    const budget = calculatedBudget(
                        updatedItem.quantity,
                        updatedItem.estimated_unit_cost,
                    );

                    if (budget !== null) {
                        updatedItem.estimated_budget = budget;
                    }
                }

                return updatedItem;
            }),
        );
    }

    function addItem() {
        setItems((currentItems) => [
            ...currentItems,
            emptyItem(currentItems.length),
        ]);
    }

    function removeItem(index: number) {
        setItems((currentItems) =>
            currentItems.length === 1
                ? currentItems
                : currentItems.filter((_, itemIndex) => itemIndex !== index),
        );
    }

    return (
        <Form {...form} className="space-y-8">
            {({ processing, errors }) => (
                <>
                    <section className="space-y-4">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="reference_no">
                                    PPMP Reference No.
                                </Label>
                                <Input
                                    id="reference_no"
                                    name="reference_no"
                                    defaultValue={ppmp?.reference_no}
                                    placeholder="PPMP-2027-ICT-001"
                                    required
                                />
                                <InputError message={errors.reference_no} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="title">PPMP Title</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    defaultValue={ppmp?.title}
                                    placeholder="ICT Procurement Plan FY 2027"
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="fiscal_year_id">
                                    Fiscal Year
                                </Label>
                                <Select
                                    name="fiscal_year_id"
                                    value={fiscalYearId}
                                    onValueChange={setFiscalYearId}
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
                                        ppmp
                                            ? String(
                                                  ppmp.organizational_unit_id,
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
                        </div>

                        {ppmp && (
                            <p className="text-muted-foreground text-sm">
                                Version {ppmp.version}. Version changes will be
                                handled through a separate revision action after
                                approval.
                            </p>
                        )}
                    </section>

                    <section className="space-y-4 border-t pt-6">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-base font-semibold">
                                    Procurement Items
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    Add the requirements that make up this PPMP.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={addItem}
                            >
                                <Plus />
                                Add item
                            </Button>
                        </div>

                        <InputError message={errors.items} />

                        <div className="space-y-5">
                            {items.map((item, index) => (
                                <div
                                    key={item.key}
                                    className="space-y-5 rounded-xl border p-4 md:p-5"
                                >
                                    <div className="flex items-center justify-between gap-3">
                                        <div>
                                            <p className="font-medium">
                                                Item {index + 1}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                Budget:{' '}
                                                {new Intl.NumberFormat(
                                                    'en-PH',
                                                    {
                                                        style: 'currency',
                                                        currency: 'PHP',
                                                    },
                                                ).format(
                                                    Number(
                                                        item.estimated_budget,
                                                    ) || 0,
                                                )}
                                            </p>
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            disabled={items.length === 1}
                                            onClick={() => removeItem(index)}
                                        >
                                            <Trash2 />
                                            Remove
                                        </Button>
                                    </div>

                                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor={`item-${index}-no`}>
                                                Item No.
                                            </Label>
                                            <Input
                                                id={`item-${index}-no`}
                                                name={`items.${index}.item_no`}
                                                value={item.item_no}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'item_no',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.item_no`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2 md:col-span-1 xl:col-span-3">
                                            <Label
                                                htmlFor={`item-${index}-title`}
                                            >
                                                Requirement Title
                                            </Label>
                                            <Input
                                                id={`item-${index}-title`}
                                                name={`items.${index}.title`}
                                                value={item.title}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'title',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.title`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2 md:col-span-2 xl:col-span-4">
                                            <Label
                                                htmlFor={`item-${index}-description`}
                                            >
                                                Description
                                            </Label>
                                            <textarea
                                                id={`item-${index}-description`}
                                                name={`items.${index}.description`}
                                                value={item.description}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'description',
                                                        event.target.value,
                                                    )
                                                }
                                                className={textareaClassName}
                                                rows={3}
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.description`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`item-${index}-category`}
                                            >
                                                Category
                                            </Label>
                                            <select
                                                id={`item-${index}-category`}
                                                name={`items.${index}.procurement_category`}
                                                value={
                                                    item.procurement_category
                                                }
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'procurement_category',
                                                        event.target.value,
                                                    )
                                                }
                                                className={selectClassName}
                                                required
                                            >
                                                <option value="">
                                                    Select category
                                                </option>
                                                {categories.map((category) => (
                                                    <option
                                                        key={category.value}
                                                        value={category.value}
                                                    >
                                                        {category.label}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.procurement_category`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`item-${index}-market-scoping`}
                                            >
                                                Market Scoping
                                            </Label>
                                            <select
                                                id={`item-${index}-market-scoping`}
                                                name={`items.${index}.market_scoping_id`}
                                                value={item.market_scoping_id}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'market_scoping_id',
                                                        event.target.value,
                                                    )
                                                }
                                                className={selectClassName}
                                            >
                                                <option value="">
                                                    Not linked
                                                </option>
                                                {availableMarketScopings.map(
                                                    (marketScoping) => (
                                                        <option
                                                            key={
                                                                marketScoping.id
                                                            }
                                                            value={
                                                                marketScoping.id
                                                            }
                                                        >
                                                            {
                                                                marketScoping.reference_no
                                                            }{' '}
                                                            —{' '}
                                                            {
                                                                marketScoping.title
                                                            }
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.market_scoping_id`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2 md:col-span-2">
                                            <Label
                                                htmlFor={`item-${index}-method`}
                                            >
                                                Recommended Procurement Method
                                            </Label>
                                            <select
                                                id={`item-${index}-method`}
                                                name={`items.${index}.recommended_procurement_method_id`}
                                                value={
                                                    item.recommended_procurement_method_id
                                                }
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'recommended_procurement_method_id',
                                                        event.target.value,
                                                    )
                                                }
                                                className={selectClassName}
                                            >
                                                <option value="">
                                                    Not selected
                                                </option>
                                                {procurementMethods.map(
                                                    (method) => (
                                                        <option
                                                            key={method.id}
                                                            value={method.id}
                                                        >
                                                            {method.code} —{' '}
                                                            {method.name}
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.recommended_procurement_method_id`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`item-${index}-quantity`}
                                            >
                                                Quantity
                                            </Label>
                                            <Input
                                                id={`item-${index}-quantity`}
                                                name={`items.${index}.quantity`}
                                                type="number"
                                                min="0.001"
                                                step="0.001"
                                                value={item.quantity}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'quantity',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.quantity`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`item-${index}-unit`}
                                            >
                                                Unit
                                            </Label>
                                            <Input
                                                id={`item-${index}-unit`}
                                                name={`items.${index}.unit`}
                                                value={item.unit}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'unit',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="lot, unit, pax..."
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.unit`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`item-${index}-unit-cost`}
                                            >
                                                Est. Unit Cost
                                            </Label>
                                            <Input
                                                id={`item-${index}-unit-cost`}
                                                name={`items.${index}.estimated_unit_cost`}
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={item.estimated_unit_cost}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'estimated_unit_cost',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="Optional"
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.estimated_unit_cost`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label
                                                htmlFor={`item-${index}-budget`}
                                            >
                                                Estimated Budget
                                            </Label>
                                            <Input
                                                id={`item-${index}-budget`}
                                                name={`items.${index}.estimated_budget`}
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                value={item.estimated_budget}
                                                readOnly={
                                                    item.estimated_unit_cost !==
                                                    ''
                                                }
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'estimated_budget',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.estimated_budget`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2 md:col-span-2">
                                            <Label
                                                htmlFor={`item-${index}-funding`}
                                            >
                                                Funding Source
                                            </Label>
                                            <Input
                                                id={`item-${index}-funding`}
                                                name={`items.${index}.funding_source`}
                                                value={item.funding_source}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'funding_source',
                                                        event.target.value,
                                                    )
                                                }
                                                placeholder="GAA, Trust Fund, Corporate Fund..."
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.funding_source`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2 md:col-span-2">
                                            <Label
                                                htmlFor={`item-${index}-quarter`}
                                            >
                                                Target Quarter
                                            </Label>
                                            <select
                                                id={`item-${index}-quarter`}
                                                name={`items.${index}.target_quarter`}
                                                value={item.target_quarter}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'target_quarter',
                                                        event.target.value,
                                                    )
                                                }
                                                className={selectClassName}
                                            >
                                                <option value="">
                                                    Not scheduled
                                                </option>
                                                <option value="1">Q1</option>
                                                <option value="2">Q2</option>
                                                <option value="3">Q3</option>
                                                <option value="4">Q4</option>
                                            </select>
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.target_quarter`
                                                    ]
                                                }
                                            />
                                        </div>

                                        <div className="grid gap-2 md:col-span-2 xl:col-span-4">
                                            <Label
                                                htmlFor={`item-${index}-remarks`}
                                            >
                                                Remarks
                                            </Label>
                                            <textarea
                                                id={`item-${index}-remarks`}
                                                name={`items.${index}.remarks`}
                                                value={item.remarks}
                                                onChange={(event) =>
                                                    updateItem(
                                                        index,
                                                        'remarks',
                                                        event.target.value,
                                                    )
                                                }
                                                className={textareaClassName}
                                                rows={2}
                                            />
                                            <InputError
                                                message={
                                                    errors[
                                                        `items.${index}.remarks`
                                                    ]
                                                }
                                            />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    <div className="flex flex-col gap-4 border-t pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-muted-foreground text-xs tracking-wide uppercase">
                                Total Estimated Budget
                            </p>
                            <p className="text-xl font-semibold tabular-nums">
                                {new Intl.NumberFormat('en-PH', {
                                    style: 'currency',
                                    currency: 'PHP',
                                }).format(totalBudget)}
                            </p>
                        </div>

                        <div className="flex justify-end gap-3">
                            <Button variant="outline" asChild>
                                <Link href={PpmpController.index()}>
                                    Cancel
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {ppmp ? 'Update PPMP draft' : 'Save PPMP draft'}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
