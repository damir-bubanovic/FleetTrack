import { destroy, index, store, update } from '@/routes/drivers';
import { apiRequest } from '@/services/apiClient';
import type { Driver } from '@/types/driver';
import type { PaginatedResponse } from '@/types/vehicle';

export type CreateDriverPayload = {
    fleet_id: number;
    employee_number: string;
    first_name: string;
    last_name: string;
    phone?: string | null;
    email?: string | null;
    license_number: string;
    license_category: string;
    license_expiry_date: string;
    employment_date: string;
    notes?: string | null;
    is_active?: boolean;
};

export type UpdateDriverPayload = CreateDriverPayload;

export function getDrivers(page = 1): Promise<PaginatedResponse<Driver>> {
    return apiRequest<PaginatedResponse<Driver>>(
        index.url({
            query: {
                page,
            },
        }),
    );
}

export function createDriver(payload: CreateDriverPayload): Promise<Driver> {
    return apiRequest<Driver>(store.url(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function updateDriver(
    driver: Driver,
    payload: UpdateDriverPayload,
): Promise<Driver> {
    return apiRequest<Driver>(update.url(driver.id), {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function deleteDriver(driver: Driver): Promise<void> {
    return apiRequest<void>(destroy.url(driver.id), {
        method: 'DELETE',
    });
}
