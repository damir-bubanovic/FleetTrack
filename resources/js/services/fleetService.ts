import { destroy, index, store, update } from '@/routes/fleets';
import { apiRequest } from '@/services/apiClient';
import type { Fleet, PaginatedResponse } from '@/types/fleet';

export type CreateFleetPayload = {
    name: string;
    code: string;
    email?: string | null;
    phone?: string | null;
    address?: string | null;
    latitude?: number | null;
    longitude?: number | null;
    timezone?: string | null;
    description?: string | null;
    is_active?: boolean;
};

export type UpdateFleetPayload = CreateFleetPayload;

export function getFleets(page = 1): Promise<PaginatedResponse<Fleet>> {
    return apiRequest<PaginatedResponse<Fleet>>(
        index.url({
            query: {
                page,
            },
        }),
    );
}

export function createFleet(payload: CreateFleetPayload): Promise<Fleet> {
    return apiRequest<Fleet>(store.url(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function updateFleet(
    fleet: Fleet,
    payload: UpdateFleetPayload,
): Promise<Fleet> {
    return apiRequest<Fleet>(update.url(fleet.id), {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function deleteFleet(fleet: Fleet): Promise<void> {
    return apiRequest<void>(destroy.url(fleet.id), {
        method: 'DELETE',
    });
}
