import { destroy, index, store, update } from '@/routes/vehicles';
import { apiRequest } from '@/services/apiClient';
import type { PaginatedResponse, Vehicle } from '@/types/vehicle';

export type CreateVehiclePayload = {
    fleet_id: number;
    registration_number: string;
    vin: string;
    manufacturer: string;
    model: string;
    year: number;
    color?: string | null;
    fuel_type: string;
    transmission: string;
    odometer?: number | null;
    notes?: string | null;
    is_active?: boolean;
};

export type UpdateVehiclePayload = CreateVehiclePayload;

export function getVehicles(
    page = 1,
    perPage?: number,
): Promise<PaginatedResponse<Vehicle>> {
    return apiRequest<PaginatedResponse<Vehicle>>(
        index.url({
            query: {
                page,
                ...(perPage !== undefined ? { per_page: perPage } : {}),
            },
        }),
    );
}

export function createVehicle(payload: CreateVehiclePayload): Promise<Vehicle> {
    return apiRequest<Vehicle>(store.url(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function updateVehicle(
    vehicle: Vehicle,
    payload: UpdateVehiclePayload,
): Promise<Vehicle> {
    return apiRequest<Vehicle>(update.url(vehicle.id), {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function deleteVehicle(vehicle: Vehicle): Promise<void> {
    return apiRequest<void>(destroy.url(vehicle.id), {
        method: 'DELETE',
    });
}
