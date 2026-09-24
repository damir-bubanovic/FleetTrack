import { destroy, index, store, update } from '@/routes/alert-rules';
import { apiRequest } from '@/services/apiClient';
import type { AlertRule, AlertRuleFormData } from '@/types/alertRule';
import type { PaginatedResponse } from '@/types/vehicle';

export function getAlertRules(page = 1): Promise<PaginatedResponse<AlertRule>> {
    return apiRequest<PaginatedResponse<AlertRule>>(
        index.url({
            query: {
                page,
            },
        }),
    );
}

export function createAlertRule(
    payload: AlertRuleFormData,
): Promise<AlertRule> {
    return apiRequest<AlertRule>(store.url(), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function updateAlertRule(
    alertRule: AlertRule,
    payload: AlertRuleFormData,
): Promise<AlertRule> {
    return apiRequest<AlertRule>(update.url(alertRule.id), {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
    });
}

export function deleteAlertRule(alertRule: AlertRule): Promise<void> {
    return apiRequest<void>(destroy.url(alertRule.id), {
        method: 'DELETE',
    });
}
