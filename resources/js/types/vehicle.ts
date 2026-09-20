export type Vehicle = {
    id: number;
    company_id: number;
    fleet_id: number;
    registration_number: string;
    vin: string;
    manufacturer: string;
    model: string;
    year: number;
    color: string | null;
    fuel_type: string;
    transmission: string;
    odometer: number;
    notes: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type PaginationMeta = {
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
};

export type PaginatedResponse<T> = {
    data: T[];
    meta: PaginationMeta;
};
