import type { Client } from './client';
import type { Product } from './product';

export type InvoiceStatus = 'draft' | 'sent' | 'partially_paid' | 'paid' | 'overdue' | 'void';

export type InvoiceItem = {
    id?: number;
    invoice_id?: number;
    product_id: number | null;
    description: string;
    quantity: number;
    unit_price: number; // minor units
    tax_rate: number;
    discount: number; // minor units
    discount_rate?: number;
    line_total?: number;
    position?: number;
    product?: Product;
};

export type Invoice = {
    id: number;
    company_id: number;
    client_id: number;
    number: string;
    invoice_number?: string;
    status: InvoiceStatus;
    issue_date: string;
    due_date: string;
    currency: string;
    subtotal: number;
    discount_total: number;
    tax_total: number;
    total: number;
    amount_paid: number;
    amount_due?: number;
    balance?: number;
    notes: string | null;
    terms?: string | null;
    public_token: string;
    sent_at: string | null;
    paid_at: string | null;
    voided_at: string | null;
    void_reason: string | null;
    created_at: string;
    updated_at: string;
    client?: Client;
    company?: {
        id: number;
        name: string;
        email?: string | null;
        address?: string | null;
    };
    items?: InvoiceItem[];
    payments?: {
        id: number;
        amount: number;
        currency: string;
        method: string;
        reference: string | null;
        paid_at: string;
        notes: string | null;
    }[];
};

export type InvoiceCalculation = {
    subtotal: number;
    discount_total: number;
    tax_total: number;
    total: number;
    items: {
        description: string;
        product_id: number | null;
        quantity: number;
        unit_price: number;
        discount: number;
        tax_rate: number;
        line_subtotal: number;
        line_tax: number;
        line_total: number;
        position: number;
    }[];
};
