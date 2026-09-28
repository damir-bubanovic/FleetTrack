import {
    destroy,
    index,
    show,
    store,
    update,
} from '@/actions/App/Http/Controllers/Api/Fleet/FleetController';
import { apiRequest } from '@/services/apiClient';
import type { Fleet, PaginatedResponse } from '@/types/fleet';

export type CreateFleetPayload = {
    company_id?: number;
    name: string;
    code: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    latitude: number | null;
    longitude: number | null;
    timezone: string | null;
    description: string | null;
    is_active: boolean;
};

export type UpdateFleetPayload = Omit<CreateFleetPayload, 'company_id'>;

export function getFleets(
    page = 1,
    perPage?: number,
): Promise<PaginatedResponse<Fleet>> {
    return apiRequest<PaginatedResponse<Fleet>>(
        index.url({
            query: {
                page,
                ...(perPage !== undefined ? { per_page: perPage } : {}),
            },
        }),
    );
}

export async function getAllFleets(): Promise<Fleet[]> {
    const firstPage = await getFleets(1, 100);
    const fleets = [...firstPage.data];

    for (let page = 2; page <= firstPage.meta.last_page; page += 1) {
        const response = await getFleets(page, 100);

        fleets.push(...response.data);
    }

    return fleets;
}

export function getFleet(fleet: Fleet): Promise<Fleet> {
    return apiRequest<Fleet>(show.url(fleet));
}

export function createFleet(payload: CreateFleetPayload): Promise<Fleet> {
    return apiRequest<Fleet>(store.url(), {
        method: 'POST',
        body: JSON.stringify(payload),
    });
}

export function updateFleet(
    fleet: Fleet,
    payload: UpdateFleetPayload,
): Promise<Fleet> {
    return apiRequest<Fleet>(update.url(fleet), {
        method: 'PUT',
        body: JSON.stringify(payload),
    });
}

export function deleteFleet(fleet: Fleet): Promise<void> {
    return apiRequest<void>(destroy.url(fleet), {
        method: 'DELETE',
    });
}
