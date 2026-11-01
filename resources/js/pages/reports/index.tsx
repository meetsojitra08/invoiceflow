import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import {
    ArrowDownToLine,
    Calendar,
    Clock,
    DollarSign,
    FileSpreadsheet,
    PieChart as PieIcon,
    Users,
} from 'lucide-react';
import PageHeader from '@/components/shared/page-header';
import StatCard from '@/components/shared/stat-card';
import Money from '@/components/shared/money';
import EmptyState from '@/components/shared/empty-state';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type ReportsData = {
    revenue_by_month: {
        month: string;
        label: string;
        revenue: number;
    }[];
    status_breakdown: {
        status: string;
        count: number;
        total_amount: number;
    }[];
    top_clients: {
        id: number;
        name: string;
        invoice_count: number;
        total_billed: number;
    }[];
    aging_buckets: {
        bucket: string;
        count: number;
        amount: number;
    }[];
};

type Props = {
    reportsData: ReportsData;
    filters: {
        from: string;
        to: string;
    };
};

const STATUS_COLORS: Record<string, string> = {
    draft: '#94a3b8',
    sent: '#3b82f6',
    partially_paid: '#f59e0b',
    paid: '#10b981',
    overdue: '#ef4444',
};

const STATUS_LABELS: Record<string, string> = {
    draft: 'Draft',
    sent: 'Sent',
    partially_paid: 'Partially Paid',
    paid: 'Paid',
    overdue: 'Overdue',
};

export default function ReportsIndex({ reportsData, filters }: Props) {
    const [fromDate, setFromDate] = useState(filters.from);
    const [toDate, setToDate] = useState(filters.to);

    const handleApplyDateRange = () => {
        router.get(
            '/reports',
            { from: fromDate, to: toDate },
            { preserveState: true, replace: true }
        );
    };

    const handleExportInvoices = () => {
        const params = new URLSearchParams({ from: fromDate, to: toDate });
        window.location.href = `/reports/export/invoices?${params.toString()}`;
    };

    const handleExportPayments = () => {
        const params = new URLSearchParams({ from: fromDate, to: toDate });
        window.location.href = `/reports/export/payments?${params.toString()}`;
    };

    // Calculate total revenue in selected range
    const totalRevenueInRange = reportsData.revenue_by_month.reduce(
        (acc, item) => acc + item.revenue,
        0
    );

    // Formatted chart data for revenue (converting minor units to major units for chart scales)
    const revenueChartData = reportsData.revenue_by_month.map((item) => ({
        ...item,
        revenueFormatted: Math.round(item.revenue / 100),
    }));

    // Formatted donut chart data
    const donutData = reportsData.status_breakdown.map((item) => ({
        name: STATUS_LABELS[item.status] || item.status,
        status: item.status,
        value: item.count,
        amount: item.total_amount,
        color: STATUS_COLORS[item.status] || '#64748b',
    }));

    return (
        <>
            <Head title="Financial Reports" />

            <div className="space-y-6">
                <PageHeader
                    title="Financial Reports"
                    description="Analytics, receivables aging, and cash collections over time."
                    action={
                        <div className="flex items-center gap-2">
                            <Button variant="outline" size="sm" onClick={handleExportInvoices}>
                                <FileSpreadsheet className="w-4 h-4 mr-2" />
                                Export Invoices CSV
                            </Button>
                            <Button variant="outline" size="sm" onClick={handleExportPayments}>
                                <ArrowDownToLine className="w-4 h-4 mr-2" />
                                Export Payments CSV
                            </Button>
                        </div>
                    }
                />

                {/* Date Filter Bar */}
                <Card>
                    <CardContent className="p-4 flex flex-wrap items-end gap-4">
                        <div className="space-y-1">
                            <Label htmlFor="report-from" className="text-xs">
                                From Date
                            </Label>
                            <Input
                                id="report-from"
                                type="date"
                                value={fromDate}
                                onChange={(e) => setFromDate(e.target.value)}
                                className="h-9 w-40 text-xs"
                            />
                        </div>

                        <div className="space-y-1">
                            <Label htmlFor="report-to" className="text-xs">
                                To Date
                            </Label>
                            <Input
                                id="report-to"
                                type="date"
                                value={toDate}
                                onChange={(e) => setToDate(e.target.value)}
                                className="h-9 w-40 text-xs"
                            />
                        </div>

                        <Button size="sm" onClick={handleApplyDateRange} className="h-9">
                            <Calendar className="w-4 h-4 mr-1.5" />
                            Apply Range
                        </Button>

                        <div className="ml-auto text-xs text-muted-foreground self-center">
                            Total collected in period:{' '}
                            <span className="font-semibold text-foreground font-mono">
                                <Money amount={totalRevenueInRange} />
                            </span>
                        </div>
                    </CardContent>
                </Card>

                {/* Aging Buckets Grid */}
                <div>
                    <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wider mb-3">
                        Accounts Receivable Aging
                    </h3>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {reportsData.aging_buckets.map((bucket) => (
                            <Card key={bucket.bucket}>
                                <CardContent className="p-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-semibold text-muted-foreground uppercase">
                                            {bucket.bucket}
                                        </span>
                                        <Clock className="w-4 h-4 text-muted-foreground" />
                                    </div>
                                    <div className="mt-2 text-xl font-bold font-mono text-foreground">
                                        <Money amount={bucket.amount} />
                                    </div>
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {bucket.count} invoice{bucket.count === 1 ? '' : 's'} outstanding
                                    </p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>

                {/* Charts Grid */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Revenue by Month Bar Chart */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <DollarSign className="w-4 h-4 text-emerald-600" />
                                Revenue Collections by Month
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Cash basis payments received per monthly period
                            </p>
                        </CardHeader>
                        <CardContent className="pt-2">
                            {revenueChartData.length === 0 || totalRevenueInRange === 0 ? (
                                <EmptyState
                                    icon={<DollarSign className="w-6 h-6 text-muted-foreground" />}
                                    title="No payment revenue yet"
                                    description="Payments recorded within this date range will appear here."
                                />
                            ) : (
                                <div className="h-72 w-full">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart data={revenueChartData} margin={{ top: 10, right: 10, left: 0, bottom: 20 }}>
                                            <CartesianGrid strokeDasharray="3 3" vertical={false} opacity={0.3} />
                                            <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                                            <YAxis
                                                tick={{ fontSize: 11 }}
                                                tickFormatter={(v) => (v >= 1000 ? `${(v / 1000).toFixed(0)}k` : `${v}`)}
                                            />
                                            <Tooltip
                                                formatter={(value: any) => [`₹${Number(value).toLocaleString()}`, 'Revenue']}
                                                labelStyle={{ fontWeight: 'bold' }}
                                            />
                                            <Bar dataKey="revenueFormatted" fill="#10b981" radius={[4, 4, 0, 0]} />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Invoice Status Breakdown Donut */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base font-semibold flex items-center gap-2">
                                <PieIcon className="w-4 h-4 text-blue-600" />
                                Invoice Status Breakdown
                            </CardTitle>
                            <p className="text-xs text-muted-foreground">
                                Distribution of invoices by lifecycle status
                            </p>
                        </CardHeader>
                        <CardContent className="pt-2">
                            {donutData.length === 0 ? (
                                <EmptyState
                                    icon={<PieIcon className="w-6 h-6 text-muted-foreground" />}
                                    title="No invoices in range"
                                    description="Invoices issued within this date range will populate the breakdown."
                                />
                            ) : (
                                <div className="h-72 w-full flex items-center justify-center">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={donutData}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={55}
                                                outerRadius={85}
                                                paddingAngle={3}
                                                dataKey="value"
                                            >
                                                {donutData.map((entry) => (
                                                    <Cell key={`cell-${entry.status}`} fill={entry.color} />
                                                ))}
                                            </Pie>
                                            <Tooltip
                                                formatter={(value: any, name: any, props: any) => [
                                                    `${value} invoice(s) (₹${(props.payload.amount / 100).toLocaleString()})`,
                                                    name,
                                                ]}
                                            />
                                            <Legend verticalAlign="bottom" height={36} iconType="circle" />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Top 10 Clients Table */}
                <Card>
                    <CardHeader className="pb-3">
                        <CardTitle className="text-base font-semibold flex items-center gap-2">
                            <Users className="w-4 h-4 text-primary" />
                            Top 10 Clients by Billed Amount
                        </CardTitle>
                        <p className="text-xs text-muted-foreground">
                            Highest spending clients during the selected period
                        </p>
                    </CardHeader>
                    <CardContent>
                        {reportsData.top_clients.length === 0 ? (
                            <EmptyState
                                icon={<Users className="w-6 h-6 text-muted-foreground" />}
                                title="No client activity in period"
                                description="Invoices issued to clients will rank them by total billed."
                            />
                        ) : (
                            <div className="overflow-x-auto rounded-lg border border-border">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-muted/50 border-b text-xs uppercase font-medium text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3">#</th>
                                            <th className="px-4 py-3">Client Name</th>
                                            <th className="px-4 py-3 text-right">Invoices Billed</th>
                                            <th className="px-4 py-3 text-right">Total Billed</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-border">
                                        {reportsData.top_clients.map((client, idx) => (
                                            <tr key={client.id} className="hover:bg-muted/30">
                                                <td className="px-4 py-3 text-xs font-mono text-muted-foreground">
                                                    {idx + 1}
                                                </td>
                                                <td className="px-4 py-3 font-medium text-foreground">
                                                    {client.name}
                                                </td>
                                                <td className="px-4 py-3 text-xs text-right text-muted-foreground">
                                                    {client.invoice_count}
                                                </td>
                                                <td className="px-4 py-3 font-mono font-semibold text-right text-foreground">
                                                    <Money amount={client.total_billed} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

