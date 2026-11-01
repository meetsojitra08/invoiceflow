export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Company = {
    id: number;
    name: string;
    currency: string;
    tax_number?: string | null;
    invoice_prefix: string;
};

export type CompanyMembership = {
    id: number;
    name: string;
    role: string;
};

export type FlashMessages = {
    success?: string | null;
    error?: string | null;
};

export type Auth = {
    user: User;
    company: Company | null;
    companies: CompanyMembership[];
    role: string | null;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
