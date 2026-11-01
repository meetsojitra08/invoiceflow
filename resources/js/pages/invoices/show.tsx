import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Check,
    Copy,
    Download,
    ExternalLink,
    Pencil,
    Send,
    XCircle,
} from 'lucide-react';
import { toast } from 'sonner';
import PageHeader from '@/components/shared/page-header';
import StatusBadge from '@/components/shared/status-badge';
import Money from '@/components/shared/money';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Invoice } from '@/types/invoice';

type Props = {
    invoice: Invoice;
    publicUrl: string;
    can: {
        edit: boolean;
        send: boolean;
        void: boolean;
        duplicate: boolean;
        delete: boolean;
        recordPayment: boolean;
    };
};

export default function InvoicesShow({ invoice, publicUrl, can }: Props) {
    const [copied, setCopied] = useState(false);
    const [showVoidModal, setShowVoidModal] = useState(false);
    const [voidReason, setVoidReason] = useState('');
    const [isVoiding, setIsVoiding] = useState(false);

    const handleCopyPublicLink = async () => {
        try {
            await navigator.clipboard.writeText(publicUrl);
            setCopied(true);
            toast.success('Public invoice link copied to clipboard.');
            setTimeout(() => setCopied(false), 2500);
        } catch {
            toast.error('Failed to copy link.');
        }
    };

    const handleConfirmVoid = () => {
        if (!voidReason.trim()) return;

        setIsVoiding(true);
        router.post(`/invoices/${invoice.id}/void`, { reason: voidReason }, {
            onSuccess: () => {
                setShowVoidModal(false);
                setVoidReason('');
                setIsVoiding(false);
            },
            onError: () => setIsVoiding(false),
        });
    };

    const balance = Math.max(0, invoice.total - (invoice.amount_paid || 0));

    return (
        <div className="space-y-6">
            <Head title={`Invoice ${invoice.number}`} />

            <PageHeader
                title={`Invoice ${invoice.number}`}
                description={`Created on ${invoice.issue_date} for ${invoice.client?.name || 'Client'}`}
                breadcrumbs={
                    <Link
                        href="/invoices"
                        className="inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft className="mr-1 h-3.5 w-3.5" />
                        Back to Invoices
                    </Link>
                }
                action={
                    <div className="flex flex-wrap items-center gap-2">
                        {can.edit && (
                            <Button asChild variant="outline" size="sm">
                                <Link href={`/invoices/${invoice.id}/edit`}>
                                    <Pencil className="mr-1.5 h-3.5 w-3.5" />
                                    Edit Draft
                                </Link>
                            </Button>
                        )}

                        {can.send && invoice.status === 'draft' && (
                            <Button asChild size="sm">
                                <Link href={`/invoices/${invoice.id}/send`} method="post" as="button">
                                    <Send className="mr-1.5 h-3.5 w-3.5" />
                                    Mark as Sent
                                </Link>
                            </Button>
                        )}

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleCopyPublicLink}
                            className="gap-1.5"
                        >
                            {copied ? <Check className="h-3.5 w-3.5 text-green-600" /> : <ExternalLink className="h-3.5 w-3.5" />}
                            Public Link
                        </Button>

                        <Button asChild variant="outline" size="sm">
                            <Link href={`/invoices/${invoice.id}/duplicate`} method="post" as="button">
                                <Copy className="mr-1.5 h-3.5 w-3.5" />
                                Duplicate
                            </Link>
                        </Button>

                        {can.void && (invoice.status === 'draft' || (invoice.status === 'sent' && (invoice.amount_paid || 0) === 0)) && (
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={() => setShowVoidModal(true)}
                            >
                                <XCircle className="mr-1.5 h-3.5 w-3.5" />
                                Void
                            </Button>
                        )}
                    </div>
                }
            />

            {/* Void Notice if applicable */}
            {invoice.status === 'void' && (
                <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <p className="font-semibold">This invoice has been voided.</p>
                    {invoice.void_reason && <p className="mt-1">Reason: {invoice.void_reason}</p>}
                </div>
            )}

            {/* Main Invoice Card */}
            <div className="rounded-lg border border-border bg-card p-6 shadow-xs space-y-6">
                <div className="flex flex-col justify-between gap-4 border-b border-border pb-6 sm:flex-row sm:items-center">
                    <div>
                        <div className="flex items-center gap-3">
                            <h2 className="text-2xl font-bold font-mono tracking-tight text-foreground">
                                {invoice.number}
                            </h2>
                            <StatusBadge status={invoice.status} />
                        </div>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Currency: <span className="font-semibold text-foreground">{invoice.currency}</span>
                        </p>
                    </div>

                    <div className="text-right sm:border-l sm:border-border sm:pl-6">
                        <p className="text-xs uppercase tracking-wider text-muted-foreground">Amount Due</p>
                        <p className="text-2xl font-bold font-mono tabular-nums text-foreground">
                            <Money amount={balance} currency={invoice.currency} />
                        </p>
                        <p className="text-xs text-muted-foreground mt-0.5">Due date: {invoice.due_date}</p>
                    </div>
                </div>

                {/* Billed To / Dates Details */}
                <div className="grid gap-6 sm:grid-cols-2">
                    <div>
                        <h4 className="text-xs uppercase tracking-wider font-semibold text-muted-foreground mb-2">
                            Billed To:
                        </h4>
                        <p className="font-semibold text-foreground">{invoice.client?.name}</p>
                        {invoice.client?.email && <p className="text-sm text-muted-foreground">{invoice.client.email}</p>}
                        {invoice.client?.phone && <p className="text-sm text-muted-foreground">{invoice.client.phone}</p>}
                        {invoice.client?.billing_address && (
                            <p className="mt-2 text-sm text-muted-foreground whitespace-pre-line">
                                {invoice.client.billing_address}
                            </p>
                        )}
                        {invoice.client?.tax_number && (
                            <p className="mt-1 text-xs text-muted-foreground">
                                Tax ID: <span className="font-mono">{invoice.client.tax_number}</span>
                            </p>
                        )}
                    </div>

                    <div className="sm:text-right space-y-1.5 text-sm">
                        <div className="flex justify-between sm:justify-end gap-4">
                            <span className="text-muted-foreground">Issue Date:</span>
                            <span className="font-medium text-foreground">{invoice.issue_date}</span>
                        </div>
                        <div className="flex justify-between sm:justify-end gap-4">
                            <span className="text-muted-foreground">Due Date:</span>
                            <span className="font-medium text-foreground">{invoice.due_date}</span>
                        </div>
                        {invoice.sent_at && (
                            <div className="flex justify-between sm:justify-end gap-4">
                                <span className="text-muted-foreground">Sent At:</span>
                                <span className="font-medium text-foreground">{invoice.sent_at}</span>
                            </div>
                        )}
                        {invoice.paid_at && (
                            <div className="flex justify-between sm:justify-end gap-4">
                                <span className="text-muted-foreground">Paid At:</span>
                                <span className="font-medium text-foreground">{invoice.paid_at}</span>
                            </div>
                        )}
                    </div>
                </div>

                {/* Items Table */}
                <div className="overflow-x-auto rounded-lg border border-border">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-muted/50 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th className="p-3">#</th>
                                <th className="p-3">Description</th>
                                <th className="p-3 text-right">Qty</th>
                                <th className="p-3 text-right">Unit Price</th>
                                <th className="p-3 text-right">Tax (%)</th>
                                <th className="p-3 text-right">Discount</th>
                                <th className="p-3 text-right">Line Total</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {invoice.items?.map((item, idx) => (
                                <tr key={item.id || idx}>
                                    <td className="p-3 text-muted-foreground font-mono">{idx + 1}</td>
                                    <td className="p-3 font-medium text-foreground">{item.description}</td>
                                    <td className="p-3 text-right font-mono tabular-nums">{item.quantity}</td>
                                    <td className="p-3 text-right font-mono tabular-nums">
                                        <Money amount={item.unit_price} currency={invoice.currency} />
                                    </td>
                                    <td className="p-3 text-right font-mono tabular-nums">{Number(item.tax_rate).toFixed(2)}%</td>
                                    <td className="p-3 text-right font-mono tabular-nums">
                                        {item.discount > 0 ? (
                                            <span className="text-destructive">
                                                - <Money amount={item.discount} currency={invoice.currency} />
                                            </span>
                                        ) : (
                                            '—'
                                        )}
                                    </td>
                                    <td className="p-3 text-right font-mono font-medium tabular-nums">
                                        <Money amount={item.line_total} currency={invoice.currency} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Notes and Totals */}
                <div className="grid gap-6 sm:grid-cols-2 pt-2">
                    <div className="text-sm">
                        {invoice.notes && (
                            <>
                                <h4 className="font-semibold text-foreground mb-1">Notes & Terms</h4>
                                <p className="text-muted-foreground whitespace-pre-line">{invoice.notes}</p>
                            </>
                        )}
                    </div>

                    <div className="space-y-2 text-sm sm:max-w-xs sm:ml-auto w-full">
                        <div className="flex justify-between text-muted-foreground">
                            <span>Subtotal:</span>
                            <span className="font-mono tabular-nums">
                                <Money amount={invoice.subtotal} currency={invoice.currency} />
                            </span>
                        </div>

                        {invoice.discount_total > 0 && (
                            <div className="flex justify-between text-destructive">
                                <span>Discount:</span>
                                <span className="font-mono tabular-nums">
                                    - <Money amount={invoice.discount_total} currency={invoice.currency} />
                                </span>
                            </div>
                        )}

                        <div className="flex justify-between text-muted-foreground">
                            <span>Tax:</span>
                            <span className="font-mono tabular-nums">
                                <Money amount={invoice.tax_total} currency={invoice.currency} />
                            </span>
                        </div>

                        <div className="border-t border-border pt-2 flex justify-between font-bold text-foreground text-base">
                            <span>Total:</span>
                            <span className="font-mono tabular-nums text-primary">
                                <Money amount={invoice.total} currency={invoice.currency} />
                            </span>
                        </div>

                        {invoice.amount_paid > 0 && (
                            <div className="flex justify-between text-green-600 font-medium pt-1">
                                <span>Amount Paid:</span>
                                <span className="font-mono tabular-nums">
                                    <Money amount={invoice.amount_paid} currency={invoice.currency} />
                                </span>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Void Modal */}
            <Dialog open={showVoidModal} onOpenChange={setShowVoidModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Void Invoice {invoice.number}</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to void this invoice? This action is permanent and cannot be undone.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3 py-2">
                        <Label htmlFor="void-modal-reason">
                            Reason for voiding <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            id="void-modal-reason"
                            placeholder="e.g. Order cancelled, billing error"
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
                            onClick={() => setShowVoidModal(false)}
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
