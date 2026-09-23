import { index } from '@/actions/App/Http/Controllers/Api/Company/CompanyController';
import { apiRequest } from '@/services/apiClient';
import type { Company } from '@/types/company';
import type { PaginatedResponse } from '@/types/vehicle';

export function getCompanies(page = 1): Promise<PaginatedResponse<Company>> {
    return apiRequest<PaginatedResponse<Company>>(
        index.url({
            query: {
                page,
            },
        }),
    );
}
