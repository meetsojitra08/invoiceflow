import { useState, useTransition } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Copy, Eye, FileText, MoreHorizontal, Pencil, Plus, Search, Send, XCircle } from 'lucide-react';
import PageHeader from '@/components/shared/page-header';
import DataTable, { Column, PaginatedData } from '@/components/shared/data-table';
import EmptyState from '@/components/shared/empty-state';
import StatusBadge from '@/components/shared/status-badge';
import Money from '@/components/shared/money';
import ConfirmDialog from '@/components/shared/confirm-dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { Invoice, InvoiceStatus } from '@/types/invoice';

type Props = {
    invoices: PaginatedData<Invoice>;
    filters: {
        search: string;
        status: string;
        from_date: string;
        to_date: string;
    };
    statusCounts: Record<string, number>;
    can: {
        create: boolean;
    };
};

const STATUS_TABS: { key: string; label: string }[] = [
    { key: 'all', label: 'All Invoices' },
    { key: 'draft', label: 'Draft' },
    { key: 'sent', label: 'Sent' },
    { key: 'partially_paid', label: 'Partially Paid' },
    { key: 'paid', label: 'Paid' },
    { key: 'overdue', label: 'Overdue' },
    { key: 'void', label: 'Void' },
];

export default function InvoicesIndex({ invoices, filters, statusCounts, can }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [, startTransition] = useTransition();
    const [voidInvoice, setVoidInvoice] = useState<Invoice | null>(null);
    const [voidReason, setVoidReason] = useState('');
    const [isVoiding, setIsVoiding] = useState(false);

    const applyFilters = (newParams: Record<string, string>) => {
        startTransition(() => {
            router.get(
                '/invoices',
                {
                    search,
                    status: filters.status,
                    from_date: filters.from_date,
                    to_date: filters.to_date,
                    ...newParams,
                },
                { preserveState: true, replace: true }
            );
        });
    };

    const handleSearchChange = (value: string) => {
        setSearch(value);
        applyFilters({ search: value });
    };

    const handleTabChange = (statusKey: string) => {
        applyFilters({ status: statusKey });
    };

    const handleConfirmVoid = () => {
        if (!voidInvoice || !voidReason.trim()) return;

        setIsVoiding(true);
        router.post(`/invoices/${voidInvoice.id}/void`, { reason: voidReason }, {
            onSuccess: () => {
                setVoidInvoice(null);
                setVoidReason('');
                setIsVoiding(false);
            },
            onError: () => setIsVoiding(false),
        });
    };

    const columns: Column<Invoice>[] = [
        {
            header: 'Invoice #',
            accessorKey: 'number',
            cell: (invoice) => (
                <Link
                    href={`/invoices/${invoice.id}`}
                    className="font-mono font-medium text-foreground hover:underline"
                >
                    {invoice.number}
                </Link>
            ),
        },
        {
            header: 'Client',
            cell: (invoice) => (
                <span className="font-medium text-foreground">{invoice.client?.name || '—'}</span>
            ),
        },
        {
            header: 'Issue Date',
            cell: (invoice) => (
                <span className="text-muted-foreground text-sm">{invoice.issue_date}</span>
            ),
        },
        {
            header: 'Due Date',
            cell: (invoice) => (
                <span className="text-muted-foreground text-sm">{invoice.due_date}</span>
            ),
        },
        {
            header: 'Status',
            cell: (invoice) => <StatusBadge status={invoice.status} />,
        },
        {
            header: 'Total',
            align: 'right',
            cell: (invoice) => <Money amount={invoice.total} currency={invoice.currency} />,
        },
        {
            header: 'Balance',
            align: 'right',
            cell: (invoice) => {
                const balance = Math.max(0, invoice.total - (invoice.amount_paid || 0));
                return (
                    <Money
                        amount={balance}
                        currency={invoice.currency}
                        className={balance > 0 && invoice.status !== 'void' ? 'font-semibold text-foreground' : 'text-muted-foreground'}
                    />
                );
            },
        },
        {
            header: 'Actions',
            align: 'right',
            cell: (invoice) => (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                            <MoreHorizontal className="h-4 w-4" />
                            <span className="sr-only">Actions</span>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                            <Link href={`/invoices/${invoice.id}`}>
                                <Eye className="mr-2 h-4 w-4" /> View Details
                            </Link>
                        </DropdownMenuItem>

                        {invoice.status === 'draft' && (
                            <DropdownMenuItem asChild>
                                <Link href={`/invoices/${invoice.id}/edit`}>
                                    <Pencil className="mr-2 h-4 w-4" /> Edit Draft
                                </Link>
                            </DropdownMenuItem>
                        )}

                        <DropdownMenuItem asChild>
                            <Link
                                href={`/invoices/${invoice.id}/duplicate`}
                                method="post"
                                as="button"
                                className="w-full text-left"
                            >
                                <Copy className="mr-2 h-4 w-4" /> Duplicate
                            </Link>
                        </DropdownMenuItem>

                        {invoice.status === 'draft' && (
                            <DropdownMenuItem asChild>
                                <Link
                                    href={`/invoices/${invoice.id}/send`}
                                    method="post"
                                    as="button"
                                    className="w-full text-left"
                                >
                                    <Send className="mr-2 h-4 w-4" /> Mark as Sent
                                </Link>
                            </DropdownMenuItem>
                        )}

                        {(invoice.status === 'draft' || (invoice.status === 'sent' && (invoice.amount_paid || 0) === 0)) && (
                            <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    onClick={() => setVoidInvoice(invoice)}
                                    className="text-destructive focus:text-destructive"
                                >
                                    <XCircle className="mr-2 h-4 w-4" /> Void Invoice
                                </DropdownMenuItem>
                            </>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    return (
        <div className="space-y-6">
            <Head title="Invoices" />

            <PageHeader
                title="Invoices"
                description="Create, send, and track billing and payments."
                action={
                    can.create && (
                        <Button asChild>
                            <Link href="/invoices/create">
                                <Plus className="mr-1.5 h-4 w-4" />
                                New Invoice
                            </Link>
                        </Button>
                    )
                }
            />

            {/* Status Filter Tabs */}
            <div className="flex flex-wrap items-center gap-1 border-b border-border pb-3">
                {STATUS_TABS.map((tab) => {
                    const isActive = (filters.status || 'all') === tab.key;
                    const count = statusCounts[tab.key] ?? 0;
                    return (
                        <Button
                            key={tab.key}
                            variant={isActive ? 'secondary' : 'ghost'}
                            size="sm"
                            onClick={() => handleTabChange(tab.key)}
                            className={`h-8 gap-1.5 px-3 text-xs font-medium ${
                                isActive ? 'bg-secondary font-semibold text-foreground' : 'text-muted-foreground'
                            }`}
                        >
                            <span>{tab.label}</span>
                            <Badge
                                variant="outline"
                                className="ml-1 h-5 min-w-5 rounded-full px-1.5 text-[10px]"
                            >
                                {count}
                            </Badge>
                        </Button>
                    );
                })}
            </div>

            {/* Search and Filters */}
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div className="relative w-full max-w-sm">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        placeholder="Search number or client..."
                        value={search}
                        onChange={(e) => handleSearchChange(e.target.value)}
                        className="pl-9"
                    />
                </div>
            </div>

            <DataTable
                columns={columns}
                data={invoices.data}
                pagination={invoices}
                keyExtractor={(invoice) => invoice.id}
                emptyState={
                    <EmptyState
                        icon={<FileText className="h-6 w-6" />}
                        title="No invoices found"
                        description={
                            filters.search || filters.status !== 'all'
                                ? 'No invoices match your current search or filter criteria.'
                                : 'Get started by creating your first invoice draft.'
                        }
                        action={
                            can.create && !filters.search && filters.status === 'all' ? (
                                <Button asChild>
                                    <Link href="/invoices/create">
                                        <Plus className="mr-1.5 h-4 w-4" />
                                        New Invoice
                                    </Link>
                                </Button>
                            ) : undefined
                        }
                    />
                }
            />

            {/* Void Invoice Reason Dialog */}
            <Dialog open={voidInvoice !== null} onOpenChange={(open) => !open && setVoidInvoice(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Void Invoice {voidInvoice?.number}</DialogTitle>
                        <DialogDescription>
                            Voiding an invoice cancels it permanently. Invoice numbers are never reused.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3 py-2">
                        <Label htmlFor="void-reason">
                            Reason for voiding <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="void-reason"
                            placeholder="e.g. Order cancelled, duplicate bill, billing error"
                            value={voidReason}
                            onChange={(e) => setVoidReason(e.target.value)}
                            required
                            autoFocus
                        />
                    </div>

                    <DialogFooter>
                        <Button
                            variant="outline"
                            type="button"
                            onClick={() => setVoidInvoice(null)}
                            disabled={isVoiding}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            type="button"
                            onClick={handleConfirmVoid}
                            disabled={isVoiding || !voidReason.trim()}
                        >
                            Confirm Void
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
