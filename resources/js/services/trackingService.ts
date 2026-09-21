import { apiRequest } from '@/services/apiClient';
import type { LivePositionsResponse } from '@/types/tracking';

export type LivePositionFilters = {
    fleetId?: number;
    vehicleId?: number;
};

export function getLivePositions(
    filters: LivePositionFilters = {},
): Promise<LivePositionsResponse> {
    const params = new URLSearchParams();

    if (filters.fleetId !== undefined) {
        params.set('fleet_id', filters.fleetId.toString());
    }

    if (filters.vehicleId !== undefined) {
        params.set('vehicle_id', filters.vehicleId.toString());
    }

    const query = params.toString();

    return apiRequest<LivePositionsResponse>(
        `/api/v1/tracking/positions${query ? `?${query}` : ''}`,
    );
}
