import { index } from '@/routes/fleets';
import { apiRequest } from '@/services/apiClient';
import type { Fleet, PaginatedResponse } from '@/types/fleet';

export function getFleets(page = 1): Promise<PaginatedResponse<Fleet>> {
    return apiRequest<PaginatedResponse<Fleet>>(
        index.url({
            query: {
                page,
            },
        }),
    );
}
