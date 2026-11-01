export type Product = {
    id: number;
    company_id: number;
    name: string;
    description: string | null;
    unit_price: number; // minor units
    tax_rate: number | string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
};
