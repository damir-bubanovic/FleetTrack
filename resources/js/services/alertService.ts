import { acknowledge, index, show } from '@/routes/alerts';
import { apiRequest } from '@/services/apiClient';
import type { Alert } from '@/types/alert';
import type { PaginatedResponse } from '@/types/vehicle';

export function getAlerts(page = 1): Promise<PaginatedResponse<Alert>> {
    return apiRequest<PaginatedResponse<Alert>>(
        index.url({
            query: {
                page,
            },
        }),
    );
}

export function getAlert(alert: Alert | number): Promise<Alert> {
    return apiRequest<Alert>(
        show.url(typeof alert === 'number' ? alert : alert.id),
    );
}

export function acknowledgeAlert(alert: Alert | number): Promise<Alert> {
    const route = acknowledge(typeof alert === 'number' ? alert : alert.id);

    return apiRequest<Alert>(route.url, {
        method: route.method.toUpperCase(),
    });
}
