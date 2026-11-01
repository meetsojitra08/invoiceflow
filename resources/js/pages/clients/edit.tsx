import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import PageHeader from '@/components/shared/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import type { Client } from '@/types/client';

type Props = {
    client: Client;
};

export default function ClientsEdit({ client }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        name: client.name || '',
        email: client.email || '',
        phone: client.phone || '',
        billing_address: client.billing_address || '',
        tax_number: client.tax_number || '',
        send_reminders: client.send_reminders ?? true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/clients/${client.id}`);
    };

    return (
        <div className="max-w-2xl space-y-6">
            <Head title={`Edit ${client.name}`} />

            <PageHeader
                title={`Edit ${client.name}`}
                description="Update client contact information and preferences."
                breadcrumbs={
                    <Link
                        href="/clients"
                        className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="mr-1 h-3.5 w-3.5" />
                        Back to Clients
                    </Link>
                }
            />

            <form onSubmit={handleSubmit} className="space-y-6 rounded-lg border border-border bg-card p-6 shadow-xs">
                <div className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">
                            Client Name <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                            autoFocus
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="phone">Phone number</Label>
                            <Input
                                id="phone"
                                type="tel"
                                value={data.phone}
                                onChange={(e) => setData('phone', e.target.value)}
                            />
                            <InputError message={errors.phone} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="tax_number">Tax Number / GSTIN</Label>
                        <Input
                            id="tax_number"
                            value={data.tax_number}
                            onChange={(e) => setData('tax_number', e.target.value)}
                        />
                        <InputError message={errors.tax_number} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="billing_address">Billing Address</Label>
                        <textarea
                            id="billing_address"
                            rows={3}
                            value={data.billing_address}
                            onChange={(e) => setData('billing_address', e.target.value)}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <InputError message={errors.billing_address} />
                    </div>

                    <div className="flex items-center space-x-2 pt-2">
                        <Checkbox
                            id="send_reminders"
                            checked={data.send_reminders}
                            onCheckedChange={(checked) => setData('send_reminders', Boolean(checked))}
                        />
                        <Label
                            htmlFor="send_reminders"
                            className="text-sm font-normal cursor-pointer leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                        >
                            Send automatic payment reminder emails for overdue invoices
                        </Label>
                    </div>
                </div>

                <div className="flex items-center justify-end gap-3 border-t border-border pt-4">
                    <Button variant="outline" type="button" asChild>
                        <Link href="/clients">Cancel</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && <Spinner className="mr-2" />}
                        Save Changes
                    </Button>
                </div>
            </form>
        </div>
    );
}
