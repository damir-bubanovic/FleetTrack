import { destroy, index, store, update } from '@/routes/geofences';
import { apiRequest } from '@/services/apiClient';
import type { Geofence, GeofenceFormData } from '@/types/geofence';
import type { PaginatedResponse } from '@/types/vehicle';

export function getGeofences(page = 1): Promise<PaginatedResponse<Geofence>> {
    return apiRequest<PaginatedResponse<Geofence>>(
        index.url({
            query: {
                page,
            },
        }),
    );
}

export function createGeofence(payload: GeofenceFormData): Promise<Geofence> {
    return apiRequest<Geofence>(store.url(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function updateGeofence(
    geofence: Geofence,
    payload: GeofenceFormData,
): Promise<Geofence> {
    return apiRequest<Geofence>(update.url(geofence.id), {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function deleteGeofence(geofence: Geofence): Promise<void> {
    return apiRequest<void>(destroy.url(geofence.id), {
        method: 'DELETE',
    });
}
