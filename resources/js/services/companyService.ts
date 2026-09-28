import { index } from '@/actions/App/Http/Controllers/Api/Company/CompanyController';
import { apiRequest } from '@/services/apiClient';
import type { Company } from '@/types/company';
import type { PaginatedResponse } from '@/types/vehicle';

export function getCompanies(
    page = 1,
    perPage?: number,
): Promise<PaginatedResponse<Company>> {
    return apiRequest<PaginatedResponse<Company>>(
        index.url({
            query: {
                page,
                ...(perPage !== undefined ? { per_page: perPage } : {}),
            },
        }),
    );
}

export async function getAllCompanies(): Promise<Company[]> {
    const firstPage = await getCompanies(1, 100);
    const companies = [...firstPage.data];

    for (let page = 2; page <= firstPage.meta.last_page; page += 1) {
        const response = await getCompanies(page, 100);

        companies.push(...response.data);
    }

    return companies;
}
