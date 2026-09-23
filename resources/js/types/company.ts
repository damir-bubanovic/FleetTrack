export type CompanyAddress = {
    address: string | null;
    city: string | null;
    state: string | null;
    postal_code: string | null;
    country: string | null;
};

export type Company = {
    id: number;
    name: string;
    slug: string;
    email: string | null;
    phone: string | null;
    address: CompanyAddress;
    logo: string | null;
    is_active: boolean;
    settings: Record<string, unknown> | null;
    created_at: string | null;
    updated_at: string | null;
};
