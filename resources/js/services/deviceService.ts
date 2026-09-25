import { destroy, index, store, update } from '@/routes/devices';
import { apiRequest } from '@/services/apiClient';
import type { Device, DeviceStatus } from '@/types/device';
import type { PaginatedResponse } from '@/types/vehicle';

export type CreateDevicePayload = {
    company_id?: number;
    vehicle_id: number | null;
    name: string;
    unique_id: string;
    status?: DeviceStatus;
};

export type UpdateDevicePayload = {
    company_id?: number;
    vehicle_id: number | null;
    name: string;
    unique_id: string;
    status: DeviceStatus;
};

export function getDevices(page = 1): Promise<PaginatedResponse<Device>> {
    return apiRequest<PaginatedResponse<Device>>(
        index.url({
            query: {
                page,
            },
        }),
    );
}

export function createDevice(payload: CreateDevicePayload): Promise<Device> {
    return apiRequest<Device>(store.url(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function updateDevice(
    device: Device,
    payload: UpdateDevicePayload,
): Promise<Device> {
    return apiRequest<Device>(update.url(device.id), {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function deleteDevice(device: Device): Promise<void> {
    return apiRequest<void>(destroy.url(device.id), {
        method: 'DELETE',
    });
}
