export type Fleet = {
    id: number;
    company_id: number;
    name: string;
    code: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    latitude: string | null;
    longitude: string | null;
    timezone: string | null;
    description: string | null;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type PaginatedResponse<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        from: number | null;
        last_page: number;
        links: PaginationLink[];
        path: string;
        per_page: number;
        to: number | null;
        total: number;
    };
};
