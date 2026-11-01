import { Head } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';
import type { Invoice } from '@/types/invoice';
import Money from '@/components/shared/money';
import StatusBadge from '@/components/shared/status-badge';
import { Button } from '@/components/ui/button';
import { Download, CreditCard } from 'lucide-react';

interface Props {
    invoice: Invoice;
}

export default function PublicInvoicePage({ invoice }: Props) {
    const isPaid = invoice.status === 'paid';
    const amountDue = invoice.amount_due ?? Math.max(0, invoice.total - invoice.amount_paid);
    const invoiceNumber = invoice.invoice_number ?? invoice.number;
    const canPay = (invoice.status === 'sent' || invoice.status === 'partially_paid') && amountDue > 0;

    return (
        <PublicLayout title={`Invoice ${invoiceNumber}`}>
            <Head title={`Invoice ${invoiceNumber}`} />

            <div className="space-y-6">
                {/* Actions banner */}
                <div className="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-4 rounded-lg border shadow-sm">
                    <div className="flex items-center gap-3">
                        <StatusBadge status={invoice.status} />
                        <span className="text-sm text-muted-foreground">
                            {isPaid
                                ? 'This invoice has been fully paid.'
                                : `Payment due by ${invoice.due_date}`}
                        </span>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            asChild
                            variant="outline"
                            size="sm"
                        >
                            <a href={`/i/${invoice.public_token}/pdf`} target="_blank" rel="noreferrer">
                                <Download className="w-4 h-4 mr-2" />
                                Download PDF
                            </a>
                        </Button>
                        {canPay && (
                            <Button size="sm">
                                <CreditCard className="w-4 h-4 mr-2" />
                                Pay <Money amount={amountDue} currency={invoice.currency} />
                            </Button>
                        )}
                    </div>
                </div>

                {/* Invoice card */}
                <div className="bg-white rounded-lg border shadow-sm p-6 sm:p-8 space-y-8">
                    {/* Header */}
                    <div className="flex flex-col sm:flex-row justify-between items-start gap-6 border-b pb-8">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-gray-900">
                                {invoice.company?.name ?? 'InvoiceFlow Company'}
                            </h1>
                            <p className="text-sm text-muted-foreground">{invoice.company?.email}</p>
                            {invoice.company?.address && (
                                <p className="text-sm text-muted-foreground whitespace-pre-line mt-1">
                                    {invoice.company.address}
                                </p>
                            )}
                        </div>

                        <div className="sm:text-right">
                            <span className="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
                                Invoice
                            </span>
                            <p className="text-2xl font-bold font-mono text-gray-900">
                                {invoiceNumber}
                            </p>
                            <div className="mt-2 space-y-1 text-sm text-muted-foreground">
                                <div><span className="font-medium text-gray-700">Issue Date:</span> {invoice.issue_date}</div>
                                <div><span className="font-medium text-gray-700">Due Date:</span> {invoice.due_date}</div>
                            </div>
                        </div>
                    </div>

                    {/* Bill To */}
                    <div>
                        <span className="text-xs uppercase tracking-wider text-muted-foreground font-semibold">
                            Billed To
                        </span>
                        <h2 className="text-lg font-semibold text-gray-900 mt-1">
                            {invoice.client?.name}
                        </h2>
                        {invoice.client?.email && (
                            <p className="text-sm text-muted-foreground">{invoice.client.email}</p>
                        )}
                        {invoice.client?.billing_address && (
                            <p className="text-sm text-muted-foreground whitespace-pre-line mt-1">
                                {invoice.client.billing_address}
                            </p>
                        )}
                        {invoice.client?.tax_number && (
                            <p className="text-sm text-muted-foreground mt-1">
                                Tax ID: {invoice.client.tax_number}
                            </p>
                        )}
                    </div>

                    {/* Items table */}
                    <div className="overflow-x-auto border rounded-lg">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-muted/50 border-b text-xs uppercase font-medium text-muted-foreground">
                                <tr>
                                    <th className="px-4 py-3">Description</th>
                                    <th className="px-4 py-3 text-right">Qty</th>
                                    <th className="px-4 py-3 text-right">Unit Price</th>
                                    <th className="px-4 py-3 text-right">Tax</th>
                                    <th className="px-4 py-3 text-right">Discount</th>
                                    <th className="px-4 py-3 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y">
                                {invoice.items?.map((item, idx) => (
                                    <tr key={item.id ?? idx}>
                                        <td className="px-4 py-3 font-medium text-gray-900">
                                            {item.description}
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono">
                                            {item.quantity}
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono">
                                            <Money amount={item.unit_price} currency={invoice.currency} />
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono text-muted-foreground">
                                            {item.tax_rate}%
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono text-muted-foreground">
                                            <Money amount={item.discount} currency={invoice.currency} />
                                        </td>
                                        <td className="px-4 py-3 text-right font-mono font-medium">
                                            <Money amount={item.line_total ?? 0} currency={invoice.currency} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* Totals */}
                    <div className="flex justify-end">
                        <div className="w-full max-w-xs space-y-2 text-sm">
                            <div className="flex justify-between text-muted-foreground">
                                <span>Subtotal</span>
                                <span className="font-mono">
                                    <Money amount={invoice.subtotal} currency={invoice.currency} />
                                </span>
                            </div>
                            {invoice.discount_total > 0 && (
                                <div className="flex justify-between text-muted-foreground">
                                    <span>Discount</span>
                                    <span className="font-mono text-emerald-600">
                                        -<Money amount={invoice.discount_total} currency={invoice.currency} />
                                    </span>
                                </div>
                            )}
                            <div className="flex justify-between text-muted-foreground">
                                <span>Tax</span>
                                <span className="font-mono">
                                    <Money amount={invoice.tax_total} currency={invoice.currency} />
                                </span>
                            </div>
                            <div className="flex justify-between font-bold text-base text-gray-900 border-t pt-2">
                                <span>Total</span>
                                <span className="font-mono">
                                    <Money amount={invoice.total} currency={invoice.currency} />
                                </span>
                            </div>
                            <div className="flex justify-between text-muted-foreground">
                                <span>Amount Paid</span>
                                <span className="font-mono">
                                    <Money amount={invoice.amount_paid} currency={invoice.currency} />
                                </span>
                            </div>
                            <div className="flex justify-between font-semibold text-gray-900 border-t pt-2">
                                <span>Amount Due</span>
                                <span className="font-mono text-primary font-bold">
                                    <Money amount={amountDue} currency={invoice.currency} />
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Notes and Terms */}
                    {(invoice.notes || invoice.terms) && (
                        <div className="border-t pt-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-muted-foreground">
                            {invoice.notes && (
                                <div>
                                    <h3 className="font-semibold text-gray-900 mb-1">Notes</h3>
                                    <p className="whitespace-pre-line">{invoice.notes}</p>
                                </div>
                            )}
                            {invoice.terms && (
                                <div>
                                    <h3 className="font-semibold text-gray-900 mb-1">Terms & Conditions</h3>
                                    <p className="whitespace-pre-line">{invoice.terms}</p>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}
