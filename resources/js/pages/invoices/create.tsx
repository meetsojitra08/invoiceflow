import InvoiceBuilder from '@/components/invoices/invoice-builder';
import type { Client } from '@/types/client';
import type { Product } from '@/types/product';

type Props = {
    clients: Client[];
    products: Product[];
    today: string;
    defaultDueDate: string;
};

export default function InvoicesCreate({ clients, products, today, defaultDueDate }: Props) {
    return (
        <InvoiceBuilder
            mode="create"
            clients={clients}
            products={products}
            today={today}
            defaultDueDate={defaultDueDate}
        />
    );
}
