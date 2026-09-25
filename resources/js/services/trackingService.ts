import { positions as vehiclePositions } from '@/routes/tracking/vehicles';
import { apiRequest } from '@/services/apiClient';
import type {
    HistoricalPositionsResponse,
    LivePositionsResponse,
} from '@/types/tracking';

export type LivePositionFilters = {
    fleetId?: number;
    vehicleId?: number;
};

export type PositionHistoryRange = {
    from: string;
    to: string;
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

export function getVehiclePositionHistory(
    vehicle: number,
    range: PositionHistoryRange,
): Promise<HistoricalPositionsResponse> {
    return apiRequest<HistoricalPositionsResponse>(
        vehiclePositions.url(vehicle, {
            query: {
                from: range.from,
                to: range.to,
            },
        }),
    );
}
