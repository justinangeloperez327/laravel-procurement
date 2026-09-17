import { Form, Link } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import AnnualProcurementPlanController from '@/actions/App/Http/Controllers/Planning/AnnualProcurementPlanController';
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

type ProcurementMethod = {
    id: number;
    code: string;
    name: string;
};

type AppType = {
    value: string;
    label: string;
};

type OrganizationalUnit = {
    id: number;
    code: string | null;
    name: string;
};

type PpmpSummary = {
    id: number;
    reference_no: string;
    fiscal_year_id: number;
    status: string;
    organizational_unit: OrganizationalUnit;
};

type PpmpItemSource = {
    id: number;
    ppmp_id: number;
    recommended_procurement_method_id: number | null;
    item_no: string;
    title: string;
    description: string | null;
    procurement_category: string;
    estimated_budget: string;
    funding_source: string | null;
    target_quarter: number | null;
    ppmp: PpmpSummary;
    recommended_procurement_method: ProcurementMethod | null;
};

type StoredAppItem = {
    id: number;
    ppmp_item_id: number;
    procurement_method_id: number | null;
    app_item_no: string;
    title: string;
    description: string | null;
    procurement_category: string;
    is_early_procurement_activity: boolean;
    bid_evaluation_criteria: string | null;
    estimated_budget: string;
    funding_source: string | null;
    schedule_start: string | null;
    schedule_end: string | null;
    procurement_strategy_tools: string[] | null;
    remarks: string | null;
};

type AppDraft = {
    id: number;
    reference_no: string;
    fiscal_year_id: number;
    app_type: string;
    version: number;
    items: StoredAppItem[];
};

type FormItem = {
    key: string;
    ppmp_item_id: string;
    procurement_method_id: string;
    app_item_no: string;
    title: string;
    description: string;
    procurement_category: string;
    is_early_procurement_activity: string;
    bid_evaluation_criteria: string;
    estimated_budget: string;
    funding_source: string;
    schedule_start: string;
    schedule_end: string;
    procurement_strategy_tools: string[];
    remarks: string;
};

const selectClassName =
    'border-input bg-background focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50';

const textareaClassName =
    'border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]';

function toMonth(value: string | null): string {
    return value ? value.slice(0, 7) : '';
}

function emptyItem(index: number): FormItem {
    return {
        key: `new-${index}-${Date.now()}`,
        ppmp_item_id: '',
        procurement_method_id: '',
        app_item_no: String(index + 1),
        title: '',
        description: '',
        procurement_category: '',
        is_early_procurement_activity: '0',
        bid_evaluation_criteria: '',
        estimated_budget: '0.00',
        funding_source: '',
        schedule_start: '',
        schedule_end: '',
        procurement_strategy_tools: [],
        remarks: '',
    };
}

function storedItemToFormItem(item: StoredAppItem): FormItem {
    return {
        key: `stored-${item.id}`,
        ppmp_item_id: String(item.ppmp_item_id),
        procurement_method_id: item.procurement_method_id
            ? String(item.procurement_method_id)
            : '',
        app_item_no: item.app_item_no,
        title: item.title,
        description: item.description ?? '',
        procurement_category: item.procurement_category,
        is_early_procurement_activity: item.is_early_procurement_activity
            ? '1'
            : '0',
        bid_evaluation_criteria: item.bid_evaluation_criteria ?? '',
        estimated_budget: item.estimated_budget,
        funding_source: item.funding_source ?? '',
        schedule_start: toMonth(item.schedule_start),
        schedule_end: toMonth(item.schedule_end),
        procurement_strategy_tools: item.procurement_strategy_tools ?? [],
        remarks: item.remarks ?? '',
    };
}

function quarterSchedule(year: number | undefined, quarter: number | null) {
    if (!year || !quarter) {
        return { start: '', end: '' };
    }

    const months = [
        ['01', '03'],
        ['04', '06'],
        ['07', '09'],
        ['10', '12'],
    ];
    const [startMonth, endMonth] = months[quarter - 1] ?? ['', ''];

    return {
        start: startMonth ? `${year}-${startMonth}` : '',
        end: endMonth ? `${year}-${endMonth}` : '',
    };
}

function formatCurrency(value: string | number) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value) || 0);
}

export default function AnnualProcurementPlanForm({
    fiscalYears,
    ppmpItems,
    procurementMethods,
    appTypes,
    bidEvaluationCriteria,
    procurementStrategyTools,
    plan,
}: {
    fiscalYears: FiscalYear[];
    ppmpItems: PpmpItemSource[];
    procurementMethods: ProcurementMethod[];
    appTypes: AppType[];
    bidEvaluationCriteria: string[];
    procurementStrategyTools: string[];
    plan?: AppDraft;
}) {
    const [fiscalYearId, setFiscalYearId] = useState(
        plan ? String(plan.fiscal_year_id) : '',
    );
    const [appType, setAppType] = useState(plan?.app_type ?? 'indicative');
    const [items, setItems] = useState<FormItem[]>(() =>
        plan?.items.length
            ? plan.items.map(storedItemToFormItem)
            : [emptyItem(0)],
    );

    const form = plan
        ? AnnualProcurementPlanController.update.form(plan.id)
        : AnnualProcurementPlanController.store.form();

    const selectedFiscalYear = fiscalYears.find(
        (fiscalYear) => String(fiscalYear.id) === fiscalYearId,
    );

    const eligibleSources = useMemo(
        () =>
            ppmpItems.filter((source) => {
                if (
                    fiscalYearId !== '' &&
                    String(source.ppmp.fiscal_year_id) !== fiscalYearId
                ) {
                    return false;
                }

                if (appType === 'indicative') {
                    return ['submitted', 'under_review', 'approved'].includes(
                        source.ppmp.status,
                    );
                }

                return source.ppmp.status === 'approved';
            }),
        [appType, fiscalYearId, ppmpItems],
    );

    const totalBudget = useMemo(
        () =>
            items.reduce((total, item) => {
                const budget = Number(item.estimated_budget);
                return total + (Number.isFinite(budget) ? budget : 0);
            }, 0),
        [items],
    );

    const earlyProcurementBudget = useMemo(
        () =>
            items.reduce((total, item) => {
                if (item.is_early_procurement_activity !== '1') {
                    return total;
                }

                const budget = Number(item.estimated_budget);
                return total + (Number.isFinite(budget) ? budget : 0);
            }, 0),
        [items],
    );

    function updateItem(
        index: number,
        field: keyof Omit<FormItem, 'key' | 'procurement_strategy_tools'>,
        value: string,
    ) {
        setItems((currentItems) =>
            currentItems.map((item, itemIndex) =>
                itemIndex === index ? { ...item, [field]: value } : item,
            ),
        );
    }

    function selectSource(index: number, sourceId: string) {
        const source = ppmpItems.find(
            (ppmpItem) => String(ppmpItem.id) === sourceId,
        );

        if (!source) {
            updateItem(index, 'ppmp_item_id', sourceId);
            return;
        }

        const schedule = quarterSchedule(
            selectedFiscalYear?.year,
            source.target_quarter,
        );

        setItems((currentItems) =>
            currentItems.map((item, itemIndex) =>
                itemIndex === index
                    ? {
                          ...item,
                          ppmp_item_id: sourceId,
                          procurement_method_id:
                              source.recommended_procurement_method_id !== null
                                  ? String(
                                        source.recommended_procurement_method_id,
                                    )
                                  : item.procurement_method_id,
                          title: source.title,
                          description: source.description ?? '',
                          procurement_category: source.procurement_category,
                          estimated_budget: source.estimated_budget,
                          funding_source: source.funding_source ?? '',
                          schedule_start: item.schedule_start || schedule.start,
                          schedule_end: item.schedule_end || schedule.end,
                      }
                    : item,
            ),
        );
    }

    function toggleStrategy(index: number, strategy: string) {
        setItems((currentItems) =>
            currentItems.map((item, itemIndex) => {
                if (itemIndex !== index) {
                    return item;
                }

                const selected =
                    item.procurement_strategy_tools.includes(strategy);

                return {
                    ...item,
                    procurement_strategy_tools: selected
                        ? item.procurement_strategy_tools.filter(
                              (tool) => tool !== strategy,
                          )
                        : [...item.procurement_strategy_tools, strategy],
                };
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
                        <div className="grid gap-6 md:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="reference_no">
                                    APP Reference No.
                                </Label>
                                <Input
                                    id="reference_no"
                                    name="reference_no"
                                    defaultValue={plan?.reference_no}
                                    placeholder="APP-2027-001"
                                    required
                                />
                                <InputError message={errors.reference_no} />
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
                                <Label htmlFor="app_type">APP Type</Label>
                                <Select
                                    name="app_type"
                                    value={appType}
                                    onValueChange={setAppType}
                                    required
                                >
                                    <SelectTrigger
                                        id="app_type"
                                        className="w-full"
                                    >
                                        <SelectValue placeholder="Select APP type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {appTypes.map((type) => (
                                            <SelectItem
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.app_type} />
                            </div>
                        </div>

                        <div className="bg-muted/40 rounded-lg border p-4 text-sm">
                            <p className="font-medium">
                                PPMP source eligibility
                            </p>
                            <p className="text-muted-foreground mt-1">
                                {appType === 'indicative'
                                    ? 'Indicative APPs can consolidate submitted, under-review, or approved PPMPs.'
                                    : 'Final and Updated APPs can consolidate approved PPMPs only.'}
                            </p>
                        </div>

                        {plan && (
                            <p className="text-muted-foreground text-sm">
                                Version {plan.version}. Approved APP revisions
                                will be handled through a separate versioning
                                action.
                            </p>
                        )}
                    </section>

                    <section className="space-y-4 border-t pt-6">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-base font-semibold">
                                    Consolidated Procurement Projects
                                </h2>
                                <p className="text-muted-foreground text-sm">
                                    Each APP line must retain a PPMP item as its
                                    source.
                                </p>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={addItem}
                            >
                                <Plus />
                                Add APP line
                            </Button>
                        </div>

                        <InputError message={errors.items} />

                        <div className="space-y-5">
                            {items.map((item, index) => {
                                const source = ppmpItems.find(
                                    (ppmpItem) =>
                                        String(ppmpItem.id) ===
                                        item.ppmp_item_id,
                                );
                                const selectedSourceIds = new Set(
                                    items
                                        .filter(
                                            (_, itemIndex) =>
                                                itemIndex !== index,
                                        )
                                        .map(
                                            (currentItem) =>
                                                currentItem.ppmp_item_id,
                                        )
                                        .filter(Boolean),
                                );

                                return (
                                    <div
                                        key={item.key}
                                        className="space-y-5 rounded-xl border p-4 md:p-5"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-medium">
                                                    APP Line {index + 1}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {source
                                                        ? `${source.ppmp.reference_no} / Item ${source.item_no} / ${source.ppmp.organizational_unit.code ?? source.ppmp.organizational_unit.name}`
                                                        : 'Select an eligible PPMP item.'}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <span className="text-sm font-medium tabular-nums">
                                                    {formatCurrency(
                                                        item.estimated_budget,
                                                    )}
                                                </span>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={
                                                        items.length === 1
                                                    }
                                                    onClick={() =>
                                                        removeItem(index)
                                                    }
                                                >
                                                    <Trash2 />
                                                    Remove
                                                </Button>
                                            </div>
                                        </div>

                                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                            <div className="grid gap-2 md:col-span-2 xl:col-span-3">
                                                <Label
                                                    htmlFor={`app-item-${index}-source`}
                                                >
                                                    Source PPMP Item
                                                </Label>
                                                <select
                                                    id={`app-item-${index}-source`}
                                                    name={`items.${index}.ppmp_item_id`}
                                                    value={item.ppmp_item_id}
                                                    onChange={(event) =>
                                                        selectSource(
                                                            index,
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={selectClassName}
                                                    required
                                                >
                                                    <option value="">
                                                        Select PPMP item
                                                    </option>
                                                    {eligibleSources
                                                        .filter(
                                                            (eligibleSource) =>
                                                                !selectedSourceIds.has(
                                                                    String(
                                                                        eligibleSource.id,
                                                                    ),
                                                                ) ||
                                                                String(
                                                                    eligibleSource.id,
                                                                ) ===
                                                                    item.ppmp_item_id,
                                                        )
                                                        .map(
                                                            (
                                                                eligibleSource,
                                                            ) => (
                                                                <option
                                                                    key={
                                                                        eligibleSource.id
                                                                    }
                                                                    value={
                                                                        eligibleSource.id
                                                                    }
                                                                >
                                                                    {
                                                                        eligibleSource
                                                                            .ppmp
                                                                            .reference_no
                                                                    }{' '}
                                                                    —{' '}
                                                                    {
                                                                        eligibleSource.item_no
                                                                    }{' '}
                                                                    —{' '}
                                                                    {
                                                                        eligibleSource.title
                                                                    }
                                                                </option>
                                                            ),
                                                        )}
                                                </select>
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.ppmp_item_id`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`app-item-${index}-number`}
                                                >
                                                    APP Item No.
                                                </Label>
                                                <Input
                                                    id={`app-item-${index}-number`}
                                                    name={`items.${index}.app_item_no`}
                                                    value={item.app_item_no}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'app_item_no',
                                                            event.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.app_item_no`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2 md:col-span-2 xl:col-span-3">
                                                <Label
                                                    htmlFor={`app-item-${index}-title`}
                                                >
                                                    Project Title
                                                </Label>
                                                <Input
                                                    id={`app-item-${index}-title`}
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

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`app-item-${index}-category`}
                                                >
                                                    Category
                                                </Label>
                                                <Input
                                                    id={`app-item-${index}-category`}
                                                    name={`items.${index}.procurement_category`}
                                                    value={
                                                        item.procurement_category
                                                    }
                                                    readOnly
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.procurement_category`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2 md:col-span-2 xl:col-span-4">
                                                <Label
                                                    htmlFor={`app-item-${index}-description`}
                                                >
                                                    General Description
                                                </Label>
                                                <textarea
                                                    id={`app-item-${index}-description`}
                                                    name={`items.${index}.description`}
                                                    value={item.description}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'description',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={
                                                        textareaClassName
                                                    }
                                                    rows={2}
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.description`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2 md:col-span-2">
                                                <Label
                                                    htmlFor={`app-item-${index}-method`}
                                                >
                                                    Mode of Procurement
                                                </Label>
                                                <select
                                                    id={`app-item-${index}-method`}
                                                    name={`items.${index}.procurement_method_id`}
                                                    value={
                                                        item.procurement_method_id
                                                    }
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'procurement_method_id',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={selectClassName}
                                                    required
                                                >
                                                    <option value="">
                                                        Select mode
                                                    </option>
                                                    {procurementMethods.map(
                                                        (method) => (
                                                            <option
                                                                key={method.id}
                                                                value={
                                                                    method.id
                                                                }
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
                                                            `items.${index}.procurement_method_id`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`app-item-${index}-epa`}
                                                >
                                                    Early Procurement Activity
                                                </Label>
                                                <select
                                                    id={`app-item-${index}-epa`}
                                                    name={`items.${index}.is_early_procurement_activity`}
                                                    value={
                                                        item.is_early_procurement_activity
                                                    }
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'is_early_procurement_activity',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={selectClassName}
                                                    required
                                                >
                                                    <option value="0">
                                                        No
                                                    </option>
                                                    <option value="1">
                                                        Yes
                                                    </option>
                                                </select>
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.is_early_procurement_activity`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`app-item-${index}-criteria`}
                                                >
                                                    Bid Evaluation Criteria
                                                </Label>
                                                <select
                                                    id={`app-item-${index}-criteria`}
                                                    name={`items.${index}.bid_evaluation_criteria`}
                                                    value={
                                                        item.bid_evaluation_criteria
                                                    }
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'bid_evaluation_criteria',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={selectClassName}
                                                >
                                                    <option value="">
                                                        Not specified
                                                    </option>
                                                    {bidEvaluationCriteria.map(
                                                        (criterion) => (
                                                            <option
                                                                key={criterion}
                                                                value={
                                                                    criterion
                                                                }
                                                            >
                                                                {criterion}
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.bid_evaluation_criteria`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`app-item-${index}-start`}
                                                >
                                                    Start of Procurement
                                                    Activity
                                                </Label>
                                                <Input
                                                    id={`app-item-${index}-start`}
                                                    name={`items.${index}.schedule_start`}
                                                    type="month"
                                                    value={item.schedule_start}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'schedule_start',
                                                            event.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.schedule_start`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`app-item-${index}-end`}
                                                >
                                                    End of Procurement Activity
                                                </Label>
                                                <Input
                                                    id={`app-item-${index}-end`}
                                                    name={`items.${index}.schedule_end`}
                                                    type="month"
                                                    value={item.schedule_end}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'schedule_end',
                                                            event.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.schedule_end`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`app-item-${index}-funding`}
                                                >
                                                    Source of Funds
                                                </Label>
                                                <Input
                                                    id={`app-item-${index}-funding`}
                                                    name={`items.${index}.funding_source`}
                                                    value={item.funding_source}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'funding_source',
                                                            event.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.funding_source`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label
                                                    htmlFor={`app-item-${index}-budget`}
                                                >
                                                    Estimated Budget / ABC
                                                </Label>
                                                <Input
                                                    id={`app-item-${index}-budget`}
                                                    name={`items.${index}.estimated_budget`}
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    value={
                                                        item.estimated_budget
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

                                            <div className="grid gap-2 md:col-span-2 xl:col-span-4">
                                                <Label>
                                                    Procurement Strategy or
                                                    Tools
                                                </Label>
                                                <div className="grid gap-2 rounded-md border p-3 sm:grid-cols-2 xl:grid-cols-3">
                                                    {procurementStrategyTools.map(
                                                        (strategy) => (
                                                            <label
                                                                key={strategy}
                                                                className="flex items-start gap-2 text-sm"
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    name={`items.${index}.procurement_strategy_tools[]`}
                                                                    value={
                                                                        strategy
                                                                    }
                                                                    checked={item.procurement_strategy_tools.includes(
                                                                        strategy,
                                                                    )}
                                                                    onChange={() =>
                                                                        toggleStrategy(
                                                                            index,
                                                                            strategy,
                                                                        )
                                                                    }
                                                                    className="mt-0.5 size-4"
                                                                />
                                                                <span>
                                                                    {strategy}
                                                                </span>
                                                            </label>
                                                        ),
                                                    )}
                                                </div>
                                                <InputError
                                                    message={
                                                        errors[
                                                            `items.${index}.procurement_strategy_tools`
                                                        ]
                                                    }
                                                />
                                            </div>

                                            <div className="grid gap-2 md:col-span-2 xl:col-span-4">
                                                <Label
                                                    htmlFor={`app-item-${index}-remarks`}
                                                >
                                                    Remarks
                                                </Label>
                                                <textarea
                                                    id={`app-item-${index}-remarks`}
                                                    name={`items.${index}.remarks`}
                                                    value={item.remarks}
                                                    onChange={(event) =>
                                                        updateItem(
                                                            index,
                                                            'remarks',
                                                            event.target.value,
                                                        )
                                                    }
                                                    className={
                                                        textareaClassName
                                                    }
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
                                );
                            })}
                        </div>
                    </section>

                    <div className="grid gap-4 border-t pt-6 md:grid-cols-2">
                        <div className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p className="text-muted-foreground text-xs tracking-wide uppercase">
                                    Total Estimated Budget
                                </p>
                                <p className="text-xl font-semibold tabular-nums">
                                    {formatCurrency(totalBudget)}
                                </p>
                            </div>
                            <div>
                                <p className="text-muted-foreground text-xs tracking-wide uppercase">
                                    EPA Projects Budget
                                </p>
                                <p className="text-xl font-semibold tabular-nums">
                                    {formatCurrency(earlyProcurementBudget)}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-end justify-end gap-3">
                            <Button variant="outline" asChild>
                                <Link
                                    href={AnnualProcurementPlanController.index()}
                                >
                                    Cancel
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {plan ? 'Update APP draft' : 'Save APP draft'}
                            </Button>
                        </div>
                    </div>
                </>
            )}
        </Form>
    );
}
