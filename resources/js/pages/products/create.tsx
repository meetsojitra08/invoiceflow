import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import PageHeader from '@/components/shared/page-header';
import MoneyInput from '@/components/shared/money-input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';

export default function ProductsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        unit_price: 0,
        tax_rate: 18.0,
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/products');
    };

    return (
        <div className="max-w-2xl space-y-6">
            <Head title="Add Product" />

            <PageHeader
                title="Add Product"
                description="Create a new billable item with default pricing and tax."
                breadcrumbs={
                    <Link
                        href="/products"
                        className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="mr-1 h-3.5 w-3.5" />
                        Back to Products
                    </Link>
                }
            />

            <form onSubmit={handleSubmit} className="space-y-6 rounded-lg border border-border bg-card p-6 shadow-xs">
                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">
                            Product / Service Name <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Web Development Consulting"
                            required
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            rows={3}
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Detailed description shown on invoices..."
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="unit_price">
                                Unit Price <span className="text-destructive">*</span>
                            </Label>
                            <MoneyInput
                                id="unit_price"
                                value={data.unit_price}
                                onChange={(minor) => setData('unit_price', minor)}
                                required
                            />
                            <InputError message={errors.unit_price} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="tax_rate">
                                Tax Rate (%) <span className="text-destructive">*</span>
                            </Label>
                            <div className="relative rounded-md shadow-xs">
                                <Input
                                    id="tax_rate"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    value={data.tax_rate}
                                    onChange={(e) => setData('tax_rate', parseFloat(e.target.value) || 0)}
                                    className="pr-8"
                                    required
                                />
                                <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <span className="text-muted-foreground text-sm">%</span>
                                </div>
                            </div>
                            <InputError message={errors.tax_rate} />
                        </div>
                    </div>

                    <div className="flex items-center space-x-2 pt-2">
                        <Checkbox
                            id="is_active"
                            checked={data.is_active}
                            onCheckedChange={(checked) => setData('is_active', Boolean(checked))}
                        />
                        <Label
                            htmlFor="is_active"
                            className="text-sm font-normal cursor-pointer leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                        >
                            Active (available for selection when creating invoices)
                        </Label>
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3 border-t border-border pt-4">
                    <Button variant="outline" type="button" asChild>
                        <Link href="/products">Cancel</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner className="mr-2" />}
                        Save Product
                    </Button>
                </div>
            </form>
        </div>
    );
}

