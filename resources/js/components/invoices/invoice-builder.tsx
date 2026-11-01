import { useEffect, useRef, useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import PageHeader from '@/components/shared/page-header';
import MoneyInput from '@/components/shared/money-input';
import Money from '@/components/shared/money';
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
import { Spinner } from '@/components/ui/spinner';
import type { Client } from '@/types/client';
import type { Product } from '@/types/product';
import type { Invoice, InvoiceCalculation } from '@/types/invoice';
import type { Auth } from '@/types/auth';

type BuilderItem = {
    rowId: string;
    product_id: string; // for select string value
    description: string;
    quantity: number;
    unit_price: number; // minor units
    tax_rate: number;
    discount: number; // minor units
    line_total?: number;
};

type Props = {
    invoice?: Invoice;
    clients: Client[];
    products: Product[];
    today?: string;
    defaultDueDate?: string;
    mode: 'create' | 'edit';
};

export default function InvoiceBuilder({
    invoice,
    clients,
    products,
    today = new Date().toISOString().split('T')[0],
    defaultDueDate = new Date(Date.now() + 14 * 86400000).toISOString().split('T')[0],
    mode,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const currency = invoice?.currency || auth.company?.currency || 'INR';

    const initialItems: BuilderItem[] = invoice?.items && invoice.items.length > 0
        ? invoice.items.map((item, i) => ({
              rowId: `item-${item.id || i}-${Math.random().toString(36).substr(2, 9)}`,
              product_id: item.product_id ? String(item.product_id) : '',
              description: item.description || '',
              quantity: Number(item.quantity) || 1,
              unit_price: item.unit_price || 0,
              tax_rate: Number(item.tax_rate) || 0,
              discount: item.discount || 0,
              line_total: item.line_total,
          }))
        : [
              {
                  rowId: `item-0-${Math.random().toString(36).substr(2, 9)}`,
                  product_id: '',
                  description: '',
                  quantity: 1,
                  unit_price: 0,
                  tax_rate: 18.0,
                  discount: 0,
                  line_total: 0,
              },
          ];

    const { data, setData, post, put, processing, errors } = useForm({
        client_id: invoice?.client_id ? String(invoice.client_id) : '',
        issue_date: invoice?.issue_date || today,
        due_date: invoice?.due_date || defaultDueDate,
        notes: invoice?.notes || '',
        currency,
        action: 'save',
        items: initialItems,
    });

    const [totals, setTotals] = useState<InvoiceCalculation>({
        subtotal: invoice?.subtotal || 0,
        discount_total: invoice?.discount_total || 0,
        tax_total: invoice?.tax_total || 0,
        total: invoice?.total || 0,
        items: [],
    });
    const [isCalculating, setIsCalculating] = useState(false);
    const debounceTimer = useRef<NodeJS.Timeout | null>(null);

    // Live calculate totals via debounced POST /invoices/calculate
    useEffect(() => {
        if (debounceTimer.current) clearTimeout(debounceTimer.current);

        setIsCalculating(true);
        debounceTimer.current = setTimeout(() => {
            const formattedItems = data.items.map((it, idx) => ({
                product_id: it.product_id ? parseInt(it.product_id, 10) : null,
                description: it.description,
                quantity: it.quantity,
                unit_price: it.unit_price,
                tax_rate: it.tax_rate,
                discount: it.discount,
                position: idx,
            }));

            fetch('/invoices/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ items: formattedItems }),
            })
                .then((res) => {
                    if (res.ok) return res.json();
                    throw new Error('Calculation error');
                })
                .then((calc: InvoiceCalculation) => {
                    setTotals(calc);
                    setIsCalculating(false);
                })
                .catch(() => setIsCalculating(false));
        }, 300);

        return () => {
            if (debounceTimer.current) clearTimeout(debounceTimer.current);
        };
    }, [data.items]);

    const handleAddItem = () => {
        setData('items', [
            ...data.items,
            {
                rowId: `item-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
                product_id: '',
                description: '',
                quantity: 1,
                unit_price: 0,
                tax_rate: 18.0,
                discount: 0,
                line_total: 0,
            },
        ]);
    };

    const handleRemoveItem = (index: number) => {
        if (data.items.length <= 1) return;
        setData('items', data.items.filter((_, i) => i !== index));
    };

    const handleItemChange = <K extends keyof BuilderItem>(
        index: number,
        field: K,
        val: BuilderItem[K]
    ) => {
        const next = [...data.items];
        next[index] = { ...next[index], [field]: val };
        setData('items', next);
    };

    const handleProductSelect = (index: number, productId: string) => {
        const product = products.find((p) => String(p.id) === productId);
        const next = [...data.items];
        if (product) {
            next[index] = {
                ...next[index],
                product_id: productId,
                description: product.description || product.name,
                unit_price: product.unit_price,
                tax_rate: Number(product.tax_rate),
            };
        } else {
            next[index] = { ...next[index], product_id: '' };
        }
        setData('items', next);
    };

    const handleSubmit = (actionType: 'save' | 'send') => {
        data.action = actionType;
        if (mode === 'create') {
            post('/invoices');
        } else if (invoice) {
            put(`/invoices/${invoice.id}`);
        }
    };

    return (
        <div className="space-y-6">
            <Head title={mode === 'create' ? 'Create Invoice' : `Edit ${invoice?.number}`} />

            <PageHeader
                title={mode === 'create' ? 'Create Invoice' : `Edit ${invoice?.number}`}
                description={
                    mode === 'create'
                        ? 'Draft a new customer invoice with line items and automatic totals.'
                        : 'Update line items, dates, and notes for this draft invoice.'
                }
                breadcrumbs={
                    <Link
                        href="/invoices"
                        className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="mr-1 h-3.5 w-3.5" />
                        Back to Invoices
                    </Link>
                }
            />

            <div className="space-y-6">
                {/* Header Information Card */}
                <div className="rounded-lg border border-border bg-card p-6 shadow-xs">
                    <div className="grid gap-6 md:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="client_id">
                                Client <span className="text-destructive">*</span>
                            </Label>
                            <Select
                                value={data.client_id}
                                onValueChange={(val) => setData('client_id', val)}
                            >
                                <SelectTrigger id="client_id">
                                    <SelectValue placeholder="Select a client..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {clients.map((c) => (
                                        <SelectItem key={c.id} value={String(c.id)}>
                                            {c.name} {c.email ? `(${c.email})` : ''}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.client_id} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="issue_date">
                                Issue Date <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="issue_date"
                                type="date"
                                value={data.issue_date}
                                onChange={(e) => setData('issue_date', e.target.value)}
                                required
                            />
                            <InputError message={errors.issue_date} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="due_date">
                                Due Date <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                id="due_date"
                                type="date"
                                value={data.due_date}
                                onChange={(e) => setData('due_date', e.target.value)}
                                required
                            />
                            <InputError message={errors.due_date} />
                        </div>
                    </div>
                </div>

                {/* Line Items Table */}
                <div className="rounded-lg border border-border bg-card shadow-xs overflow-hidden">
                    <div className="p-4 bg-muted/40 border-b border-border flex items-center justify-between">
                        <h3 className="font-semibold text-sm text-foreground">Invoice Line Items</h3>
                        <Button type="button" variant="outline" size="sm" onClick={handleAddItem}>
                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Add Row
                        </Button>
                    </div>

                    <div className="overflow-x-auto p-4">
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-border text-xs text-muted-foreground uppercase">
                                    <th className="pb-3 w-44">Autofill Product</th>
                                    <th className="pb-3 min-w-[200px]">Description *</th>
                                    <th className="pb-3 w-24 text-right">Qty *</th>
                                    <th className="pb-3 w-36 text-right">Unit Price *</th>
                                    <th className="pb-3 w-28 text-right">Tax (%)</th>
                                    <th className="pb-3 w-32 text-right">Discount</th>
                                    <th className="pb-3 w-32 text-right">Amount</th>
                                    <th className="pb-3 w-10"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {data.items.map((item, index) => {
                                    const calculatedItem = totals.items[index];
                                    const lineTotal = calculatedItem ? calculatedItem.line_total : item.line_total || 0;

                                    return (
                                        <tr key={item.rowId} className="align-top group">
                                            <td className="pt-3 pr-2">
                                                <Select
                                                    value={item.product_id}
                                                    onValueChange={(val) => handleProductSelect(index, val)}
                                                >
                                                    <SelectTrigger className="h-9 text-xs">
                                                        <SelectValue placeholder="Pick item..." />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {products.map((p) => (
                                                            <SelectItem key={p.id} value={String(p.id)}>
                                                                {p.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </td>

                                            <td className="pt-3 pr-2">
                                                <Input
                                                    value={item.description}
                                                    onChange={(e) =>
                                                        handleItemChange(index, 'description', e.target.value)
                                                    }
                                                    placeholder="Description of product or service"
                                                    required
                                                    className="h-9"
                                                />
                                            </td>

                                            <td className="pt-3 pr-2">
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0.01"
                                                    value={item.quantity}
                                                    onChange={(e) =>
                                                        handleItemChange(
                                                            index,
                                                            'quantity',
                                                            parseFloat(e.target.value) || 0
                                                        )
                                                    }
                                                    required
                                                    className="h-9 text-right font-mono tabular-nums"
                                                />
                                            </td>

                                            <td className="pt-3 pr-2">
                                                <MoneyInput
                                                    value={item.unit_price}
                                                    onChange={(minor) =>
                                                        handleItemChange(index, 'unit_price', minor)
                                                    }
                                                    currency={currency}
                                                    className="h-9"
                                                />
                                            </td>

                                            <td className="pt-3 pr-2">
                                                <Input
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    value={item.tax_rate}
                                                    onChange={(e) =>
                                                        handleItemChange(
                                                            index,
                                                            'tax_rate',
                                                            parseFloat(e.target.value) || 0
                                                        )
                                                    }
                                                    className="h-9 text-right font-mono tabular-nums"
                                                />
                                            </td>

                                            <td className="pt-3 pr-2">
                                                <MoneyInput
                                                    value={item.discount}
                                                    onChange={(minor) =>
                                                        handleItemChange(index, 'discount', minor)
                                                    }
                                                    currency={currency}
                                                    className="h-9"
                                                />
                                            </td>

                                            <td className="pt-3 pr-2 text-right pt-4 font-mono font-medium tabular-nums">
                                                <Money amount={lineTotal} currency={currency} />
                                            </td>

                                            <td className="pt-3 text-right">
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleRemoveItem(index)}
                                                    disabled={data.items.length <= 1}
                                                    className="h-9 w-9 p-0 text-muted-foreground hover:text-destructive"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    <div className="p-4 border-t border-border bg-muted/20">
                        <Button type="button" variant="outline" size="sm" onClick={handleAddItem}>
                            <Plus className="mr-1.5 h-3.5 w-3.5" /> Add Another Row
                        </Button>
                        <InputError message={errors.items} className="mt-2" />
                    </div>
                </div>

                {/* Bottom Section: Notes and Totals Panel */}
                <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                        <Label htmlFor="notes">Notes / Payment Terms</Label>
                        <textarea
                            id="notes"
                            rows={5}
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                            placeholder="Add bank details, payment terms, or thank you note..."
                            className="w-full rounded-md border border-input bg-background p-3 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="rounded-lg border border-border bg-card p-6 shadow-xs flex flex-col justify-between">
                        <div className="space-y-3 text-sm">
                            <div className="flex justify-between items-center text-muted-foreground">
                                <span>Subtotal:</span>
                                <span className="font-mono tabular-nums">
                                    <Money amount={totals.subtotal} currency={currency} />
                                </span>
                            </div>

                            {totals.discount_total > 0 && (
                                <div className="flex justify-between items-center text-muted-foreground">
                                    <span>Discount Total:</span>
                                    <span className="font-mono tabular-nums text-destructive">
                                        - <Money amount={totals.discount_total} currency={currency} />
                                    </span>
                                </div>
                            )}

                            <div className="flex justify-between items-center text-muted-foreground">
                                <span>Tax Total:</span>
                                <span className="font-mono tabular-nums">
                                    <Money amount={totals.tax_total} currency={currency} />
                                </span>
                            </div>

                            <div className="border-t border-border pt-3 flex justify-between items-center text-base font-bold text-foreground">
                                <span className="flex items-center gap-2">
                                    Total:
                                    {isCalculating && <Spinner className="h-3.5 w-3.5" />}
                                </span>
                                <span className="font-mono tabular-nums text-lg text-primary">
                                    <Money amount={totals.total} currency={currency} />
                                </span>
                            </div>
                        </div>

                        {/* Submit Actions */}
                        <div className="mt-6 flex flex-wrap items-center justify-end gap-3 border-t border-border pt-4">
                            <Button variant="outline" type="button" asChild>
                                <Link href="/invoices">Cancel</Link>
                            </Button>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => handleSubmit('save')}
                                disabled={processing}
                            >
                                {processing && <Spinner className="mr-2" />}
                                Save Draft
                            </Button>
                            <Button
                                type="button"
                                onClick={() => handleSubmit('send')}
                                disabled={processing}
                            >
                                {processing && <Spinner className="mr-2" />}
                                Save & Send
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
