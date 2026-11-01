import { Head, Link } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    Clock,
    CreditCard,
    FilePlus,
    FileText,
    Send,
    TrendingUp,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import PageHeader from '@/components/shared/page-header';
import StatCard from '@/components/shared/stat-card';
import StatusBadge from '@/components/shared/status-badge';
import Money from '@/components/shared/money';
import EmptyState from '@/components/shared/empty-state';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Invoice } from '@/types/invoice';

type Props = {
    kpis: {
        revenue_this_month: number | null;
        total_outstanding: number | null;
        overdue_amount: number | null;
        overdue_count: number;
        invoices_sent_this_month: number;
        can_view_revenue: boolean;
    };
    recentInvoices: Invoice[];
    overdueInvoices: Invoice[];
    userRole: string;
};

export default function Dashboard({ kpis, recentInvoices, overdueInvoices, userRole }: Props) {
    const isOwnerOrAccountant = ['owner', 'accountant'].includes(userRole);

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="space-y-6">
                <PageHeader
                    title="Dashboard"
                    description="Welcome back. Here is the operational summary of your invoicing activity."
                    action={
                        <div className="flex gap-2">
                            <Button asChild>
                                <Link href="/invoices/create">
                                    <FilePlus className="w-4 h-4 mr-2" />
                                    New Invoice
                                </Link>
                            </Button>
                        </div>
                    }
                />

                {/* KPI Cards Grid */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {kpis.can_view_revenue ? (
                        <StatCard
                            title="Revenue This Month"
                            value={<Money amount={kpis.revenue_this_month ?? 0} />}
                            description="Payments received this month"
                            icon={<TrendingUp className="w-5 h-5 text-emerald-600" />}
                        />
                    ) : (
                        <StatCard
                            title="Invoices Sent"
                            value={kpis.invoices_sent_this_month}
                            description="Sent this month"
                            icon={<Send className="w-5 h-5 text-blue-600" />}
                        />
                    )}

                    {kpis.can_view_revenue && (
                        <StatCard
                            title="Total Outstanding"
                            value={<Money amount={kpis.total_outstanding ?? 0} />}
                            description="Unpaid sent & overdue invoices"
                            icon={<Clock className="w-5 h-5 text-amber-600" />}
                        />
                    )}

                    {kpis.can_view_revenue ? (
                        <StatCard
                            title="Overdue Invoices"
                            value={<Money amount={kpis.overdue_amount ?? 0} />}
                            description={`${kpis.overdue_count} invoice(s) past due`}
                            icon={<AlertTriangle className="w-5 h-5 text-rose-600" />}
                        />
                    ) : (
                        <StatCard
                            title="Overdue Invoices"
                            value={kpis.overdue_count}
                            description="Invoices past due date"
                            icon={<AlertTriangle className="w-5 h-5 text-rose-600" />}
                        />
                    )}

                    <StatCard
                        title="Sent This Month"
                        value={kpis.invoices_sent_this_month}
                        description="New invoices issued"
                        icon={<Send className="w-5 h-5 text-indigo-600" />}
                    />
                </div>

                {/* Recent & Overdue Invoices Grids */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Overdue Invoices Box */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-3">
                            <div>
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <AlertTriangle className="w-4 h-4 text-rose-500" />
                                    Overdue Invoices
                                </CardTitle>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    Requires collection attention (oldest first)
                                </p>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href="/invoices?status=overdue" className="text-xs">
                                    View all <ArrowRight className="w-3.5 h-3.5 ml-1" />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {overdueInvoices.length === 0 ? (
                                <EmptyState
                                    icon={<AlertTriangle className="w-6 h-6 text-muted-foreground" />}
                                    title="No overdue invoices"
                                    description="All outstanding invoices are currently on schedule."
                                />
                            ) : (
                                <div className="divide-y divide-border">
                                    {overdueInvoices.map((inv) => (
                                        <div
                                            key={inv.id}
                                            className="py-3 flex items-center justify-between hover:bg-muted/40 px-2 rounded-md transition-colors"
                                        >
                                            <div className="space-y-0.5">
                                                <Link
                                                    href={`/invoices/${inv.id}`}
                                                    className="font-mono text-sm font-semibold text-primary hover:underline"
                                                >
                                                    {inv.number}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {inv.client?.name ?? 'Unknown client'} • Due {inv.due_date}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <div className="font-mono font-semibold text-sm text-foreground">
                                                    <Money amount={inv.total - inv.amount_paid} currency={inv.currency} />
                                                </div>
                                                <StatusBadge status={inv.status} />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Invoices Box */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-3">
                            <div>
                                <CardTitle className="text-base font-semibold flex items-center gap-2">
                                    <FileText className="w-4 h-4 text-primary" />
                                    Recent Invoices
                                </CardTitle>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    Latest invoices created in this workspace
                                </p>
                            </div>
                            <Button variant="ghost" size="sm" asChild>
                                <Link href="/invoices" className="text-xs">
                                    View all <ArrowRight className="w-3.5 h-3.5 ml-1" />
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {recentInvoices.length === 0 ? (
                                <EmptyState
                                    icon={<FileText className="w-6 h-6 text-muted-foreground" />}
                                    title="No invoices yet"
                                    description="Create your first invoice to get started."
                                    action={
                                        <Button size="sm" asChild>
                                            <Link href="/invoices/create">Create Invoice</Link>
                                        </Button>
                                    }
                                />
                            ) : (
                                <div className="divide-y divide-border">
                                    {recentInvoices.map((inv) => (
                                        <div
                                            key={inv.id}
                                            className="py-3 flex items-center justify-between hover:bg-muted/40 px-2 rounded-md transition-colors"
                                        >
                                            <div className="space-y-0.5">
                                                <Link
                                                    href={`/invoices/${inv.id}`}
                                                    className="font-mono text-sm font-semibold text-primary hover:underline"
                                                >
                                                    {inv.number}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {inv.client?.name ?? 'Unknown client'} • {inv.issue_date}
                                                </p>
                                            </div>
                                            <div className="text-right">
                                                <div className="font-mono font-semibold text-sm text-foreground">
                                                    <Money amount={inv.total} currency={inv.currency} />
                                                </div>
                                                <StatusBadge status={inv.status} />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
