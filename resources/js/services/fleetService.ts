import { index } from '@/routes/fleets';
import type { Fleet, PaginatedResponse } from '@/types/fleet';

export async function getFleets(page = 1): Promise<PaginatedResponse<Fleet>> {
    const response = await fetch(
        index.url({
            query: {
                page,
            },
        }),
        {
            headers: {
                Accept: 'application/json',
            },
        },
    );

    if (!response.ok) {
        throw new Error(`Failed to load fleets (${response.status}).`);
    }

    return (await response.json()) as PaginatedResponse<Fleet>;
}
