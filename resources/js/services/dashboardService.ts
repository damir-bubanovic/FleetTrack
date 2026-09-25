import { overview } from '@/routes/dashboard';
import { apiRequest } from '@/services/apiClient';
import type { DashboardOverview } from '@/types/dashboard';

type DashboardOverviewResponse = {
    data: DashboardOverview;
};

export async function getDashboardOverview(): Promise<DashboardOverview> {
    const response = await apiRequest<DashboardOverviewResponse>(
        overview.url(),
    );

    return response.data;
}
