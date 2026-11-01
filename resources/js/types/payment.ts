import type { Invoice } from './invoice';

export type PaymentMethodType = 'bank_transfer' | 'cash' | 'credit_card' | 'stripe' | 'other';

export type Payment = {
    id: number;
    company_id: number;
    invoice_id: number;
    amount: number; // minor units
    currency: string;
    method: PaymentMethodType;
    reference: string | null;
    paid_at: string;
    notes: string | null;
    created_at: string;
    invoice?: Invoice;
};
