import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Edit, FileText, Mail, MapPin, Phone } from 'lucide-react';
import PageHeader from '@/components/shared/page-header';
import StatCard from '@/components/shared/stat-card';
import Money from '@/components/shared/money';
import EmptyState from '@/components/shared/empty-state';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { Client } from '@/types/client';

type Props = {
    client: Client;
    stats: {
        total_billed: number;
        outstanding_balance: number;
        invoices_count: number;
    };
    invoices: unknown[];
};

export default function ClientsShow({ client, stats, invoices }: Props) {
    return (
        <div className="space-y-6">
            <Head title={client.name} />

            <PageHeader
                title={client.name}
                description="Client details and invoicing history."
                breadcrumbs={
                    <Link
                        href="/clients"
                        className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="mr-1 h-3.5 w-3.5" />
                        Back to Clients
                    </Link>
                }
                action={
                    <Button asChild variant="outline">
                        <Link href={`/clients/${client.id}/edit`}>
                            <Edit className="mr-1.5 h-4 w-4" />
                            Edit Client
                        </Link>
                    </Button>
                }
            />

            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard
                    title="Total Billed"
                    value={<Money amount={stats.total_billed} />}
                />
                <StatCard
                    title="Outstanding Balance"
                    value={<Money amount={stats.outstanding_balance} />}
                    className={stats.outstanding_balance > 0 ? 'border-amber-300' : ''}
                />
                <StatCard
                    title="Invoices"
                    value={stats.invoices_count}
                />
            </div>

            <div className="grid gap-6 md:grid-cols-3">
                <Card className="md:col-span-1">
                    <CardHeader>
                        <CardTitle className="text-base">Contact Information</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4 text-sm">
                        <div className="flex items-start gap-3">
                            <Mail className="mt-0.5 h-4 w-4 text-muted-foreground" />
                            <div>
                                <p className="font-medium text-foreground">Email</p>
                                <p className="text-muted-foreground">{client.email || 'Not provided'}</p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3">
                            <Phone className="mt-0.5 h-4 w-4 text-muted-foreground" />
                            <div>
                                <p className="font-medium text-foreground">Phone</p>
                                <p className="text-muted-foreground">{client.phone || 'Not provided'}</p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3">
                            <MapPin className="mt-0.5 h-4 w-4 text-muted-foreground" />
                            <div>
                                <p className="font-medium text-foreground">Billing Address</p>
                                <p className="text-muted-foreground whitespace-pre-line">
                                    {client.billing_address || 'Not provided'}
                                </p>
                            </div>
                        </div>

                        {client.tax_number && (
                            <div className="pt-2 border-t border-border">
                                <p className="font-medium text-foreground">Tax Number / GSTIN</p>
                                <p className="text-muted-foreground font-mono">{client.tax_number}</p>
                            </div>
                        )}

                        <div className="pt-2 border-t border-border">
                            <p className="font-medium text-foreground mb-1">Automatic Reminders</p>
                            <Badge variant={client.send_reminders ? 'secondary' : 'outline'}>
                                {client.send_reminders ? 'Enabled' : 'Disabled'}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card className="md:col-span-2">
                    <CardHeader>
                        <CardTitle className="text-base">Invoice History</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {invoices.length === 0 ? (
                            <EmptyState
                                icon={<FileText className="h-6 w-6" />}
                                title="No invoices yet"
                                description="Invoices created for this client will show up here."
                            />
                        ) : (
                            <div>Invoice list</div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

