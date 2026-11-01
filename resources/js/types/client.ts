export type Client = {
    id: number;
    company_id: number;
    name: string;
    email: string | null;
    phone: string | null;
    billing_address: string | null;
    tax_number: string | null;
    send_reminders: boolean;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
};

