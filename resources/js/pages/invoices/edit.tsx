import InvoiceBuilder from '@/components/invoices/invoice-builder';
import type { Client } from '@/types/client';
import type { Product } from '@/types/product';
import type { Invoice } from '@/types/invoice';

type Props = {
    invoice: Invoice;
    clients: Client[];
    products: Product[];
};

export default function InvoicesEdit({ invoice, clients, products }: Props) {
    return (
        <InvoiceBuilder
            mode="edit"
            invoice={invoice}
            clients={clients}
            products={products}
        />
    );
}
