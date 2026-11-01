import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import PageHeader from '@/components/shared/page-header';
import StatCard from '@/components/shared/stat-card';
import Money from '@/components/shared/money';
import EmptyState from '@/components/shared/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Payment } from '@/types/payment';
import { CreditCard, Search, ArrowUpDown } from 'lucide-react';
import { formatDate } from '@/lib/utils';

interface Props {
    payments: {
        data: Payment[];
        current_page: number;
        last_page: number;
        total: number;
        from: number;
        to: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    totalCollected: number;
    filters: {
        method: string;
        from_date: string;
        to_date: string;
        search: string;
    };
}

export default function PaymentsIndexPage({ payments, totalCollected, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [method, setMethod] = useState(filters.method || 'all');
    const [fromDate, setFromDate] = useState(filters.from_date || '');
    const [toDate, setToDate] = useState(filters.to_date || '');

    const applyFilter = (newFilters: Partial<typeof filters>) => {
        router.get('/payments', {
            ...filters,
            ...newFilters,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleSearchSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        applyFilter({ search });
    };

    const getMethodBadgeVariant = (m: string) => {
        switch (m) {
            case 'stripe':
                return 'default';
            case 'bank_transfer':
                return 'secondary';
            case 'credit_card':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    const formatMethodLabel = (m: string) => {
        return m.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase());
    };

    return (
        <>
            <Head title="Payments" />

            <div className="space-y-6">
                <PageHeader
                    title="Payments"
                    description="Record and track payments collected against client invoices."
                />

                {/* KPI Card */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <StatCard
                        title="Total Collected"
                        value={<Money amount={totalCollected} />}
                        description={`${payments.total} total payment transaction(s)`}
                    />
                </div>

                {/* Filters */}
                <div className="bg-card rounded-lg border p-4 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-stretch md:items-center">
                    <form onSubmit={handleSearchSubmit} className="flex-1 max-w-sm flex items-center gap-2">
                        <div className="relative w-full">
                            <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search reference, invoice..."
                                className="pl-8"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                        <Button type="submit" variant="secondary" size="sm">
                            Search
                        </Button>
                    </form>

                    <div className="flex flex-wrap items-center gap-3">
                        <Select
                            value={method}
                            onValueChange={(val) => {
                                setMethod(val);
                                applyFilter({ method: val });
                            }}
                        >
                            <SelectTrigger className="w-36 h-9 text-xs">
                                <SelectValue placeholder="Payment Method" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Methods</SelectItem>
                                <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                                <SelectItem value="cash">Cash</SelectItem>
                                <SelectItem value="credit_card">Credit Card</SelectItem>
                                <SelectItem value="stripe">Stripe</SelectItem>
                                <SelectItem value="other">Other</SelectItem>
                            </SelectContent>
                        </Select>

                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <span>From:</span>
                            <Input
                                type="date"
                                className="w-36 h-9 text-xs"
                                value={fromDate}
                                onChange={(e) => {
                                    setFromDate(e.target.value);
                                    applyFilter({ from_date: e.target.value });
                                }}
                            />
                        </div>

                        <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <span>To:</span>
                            <Input
                                type="date"
                                className="w-36 h-9 text-xs"
                                value={toDate}
                                onChange={(e) => {
                                    setToDate(e.target.value);
                                    applyFilter({ to_date: e.target.value });
                                }}
                            />
                        </div>
                    </div>
                </div>

                {/* Table */}
                {payments.data.length === 0 ? (
                    <EmptyState
                        icon={<CreditCard className="w-6 h-6" />}
                        title="No payments recorded"
                        description="Payments will appear here when recorded against invoices."
                    />
                ) : (
                    <div className="bg-card rounded-lg border shadow-sm overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted/50 border-b text-xs uppercase font-medium text-muted-foreground">
                                    <tr>
                                        <th className="px-6 py-3">Date</th>
                                        <th className="px-6 py-3">Invoice</th>
                                        <th className="px-6 py-3">Client</th>
                                        <th className="px-6 py-3">Method</th>
                                        <th className="px-6 py-3">Reference</th>
                                        <th className="px-6 py-3 text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {payments.data.map((payment) => (
                                        <tr key={payment.id} className="hover:bg-muted/30">
                                            <td className="px-6 py-4 text-xs text-muted-foreground whitespace-nowrap">
                                                {formatDate(payment.paid_at)}
                                            </td>
                                            <td className="px-6 py-4 font-mono font-medium">
                                                {payment.invoice ? (
                                                    <Link
                                                        href={`/invoices/${payment.invoice.id}`}
                                                        className="text-primary hover:underline"
                                                    >
                                                        {payment.invoice.number}
                                                    </Link>
                                                ) : (
                                                    '-'
                                                )}
                                            </td>
                                            <td className="px-6 py-4">
                                                {payment.invoice?.client?.name || '-'}
                                            </td>
                                            <td className="px-6 py-4">
                                                <Badge variant={getMethodBadgeVariant(payment.method)}>
                                                    {formatMethodLabel(payment.method)}
                                                </Badge>
                                            </td>
                                            <td className="px-6 py-4 text-xs font-mono text-muted-foreground">
                                                {payment.reference || '-'}
                                            </td>
                                            <td className="px-6 py-4 text-right font-mono font-bold text-foreground">
                                                <Money amount={payment.amount} currency={payment.currency} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {payments.last_page > 1 && (
                            <div className="p-4 border-t flex items-center justify-between text-xs text-muted-foreground">
                                <div>
                                    Showing {payments.from} to {payments.to} of {payments.total} payments
                                </div>
                                <div className="flex gap-1">
                                    {payments.links.map((link, i) => (
                                        <Button
                                            key={i}
                                            variant={link.active ? 'default' : 'outline'}
                                            size="sm"
                                            asChild={!!link.url}
                                            disabled={!link.url}
                                        >
                                            {link.url ? (
                                                <Link href={link.url}>
                                                    {link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                                                </Link>
                                            ) : (
                                                <span>
                                                    {link.label.replace('&laquo;', '«').replace('&raquo;', '»')}
                                                </span>
                                            )}
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}
