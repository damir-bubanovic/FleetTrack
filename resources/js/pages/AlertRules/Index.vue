<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import AlertRuleForm from '@/components/alert-rules/AlertRuleForm.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppCard from '@/components/ui/AppCard.vue';
import AppPagination from '@/components/ui/AppPagination.vue';
import AppTable from '@/components/ui/AppTable.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ErrorState from '@/components/ui/ErrorState.vue';
import LoadingState from '@/components/ui/LoadingState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { deleteAlertRule, getAlertRules } from '@/services/alertRuleService';
import { getVehicles } from '@/services/vehicleService';
import type {
    AlertRule,
    AlertRuleSeverity,
    AlertRuleType,
} from '@/types/alertRule';
import type { PaginatedResponse, Vehicle } from '@/types/vehicle';

const alertRulesResponse = ref<PaginatedResponse<AlertRule> | null>(null);

const vehicles = ref<Vehicle[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creatingAlertRule = ref(false);
const editingAlertRule = ref<AlertRule | null>(null);
const deletingAlertRuleId = ref<number | null>(null);

const alertRules = computed(() => alertRulesResponse.value?.data ?? []);

const vehicleNames = computed(() => {
    return new Map(
        vehicles.value.map((vehicle) => [
            vehicle.id,
            `${vehicle.manufacturer} ${vehicle.model} (${vehicle.registration_number})`,
        ]),
    );
});

async function loadAlertRules(page = 1): Promise<void> {
    if (page < 1) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        alertRulesResponse.value = await getAlertRules(page);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load alert rules.';
    } finally {
        loading.value = false;
    }
}

async function loadVehicles(): Promise<void> {
    try {
        const response = await getVehicles(1, 100);

        vehicles.value = response.data;
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load vehicles.';
    }
}

function startCreatingAlertRule(): void {
    editingAlertRule.value = null;
    creatingAlertRule.value = true;
}

function startEditingAlertRule(alertRule: AlertRule): void {
    creatingAlertRule.value = false;
    editingAlertRule.value = alertRule;
}

function cancelAlertRuleForm(): void {
    creatingAlertRule.value = false;
    editingAlertRule.value = null;
}

async function handleAlertRuleSaved(): Promise<void> {
    cancelAlertRuleForm();

    await loadAlertRules(1);
}

async function handleDeleteAlertRule(alertRule: AlertRule): Promise<void> {
    const confirmed = window.confirm(
        `Delete "${alertRule.name}"? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    deletingAlertRuleId.value = alertRule.id;
    error.value = null;

    try {
        await deleteAlertRule(alertRule);

        if (editingAlertRule.value?.id === alertRule.id) {
            cancelAlertRuleForm();
        }

        await loadAlertRules(alertRulesResponse.value?.meta.current_page ?? 1);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to delete alert rule.';
    } finally {
        deletingAlertRuleId.value = null;
    }
}

function typeLabel(type: AlertRuleType): string {
    const labels: Record<AlertRuleType, string> = {
        overspeed: 'Overspeed',
        geofence_enter: 'Geofence enter',
        geofence_exit: 'Geofence exit',
        ignition_on: 'Ignition on',
        ignition_off: 'Ignition off',
        device_offline: 'Device offline',
    };

    return labels[type];
}

function severityLabel(severity: AlertRuleSeverity): string {
    return severity.charAt(0).toUpperCase() + severity.slice(1);
}

function severityVariant(
    severity: AlertRuleSeverity,
): 'info' | 'warning' | 'danger' {
    if (severity === 'critical') {
        return 'danger';
    }

    if (severity === 'warning') {
        return 'warning';
    }

    return 'info';
}

function vehicleLabel(alertRule: AlertRule): string {
    if (alertRule.vehicle_id === null) {
        return 'All vehicles';
    }

    return (
        vehicleNames.value.get(alertRule.vehicle_id) ??
        `Vehicle #${alertRule.vehicle_id}`
    );
}

function conditionsLabel(alertRule: AlertRule): string {
    if (
        alertRule.type === 'overspeed' &&
        alertRule.conditions.speed_limit_kmh !== undefined
    ) {
        return `${alertRule.conditions.speed_limit_kmh} km/h`;
    }

    return '—';
}

async function loadPage(): Promise<void> {
    await Promise.all([loadAlertRules(), loadVehicles()]);
}

onMounted(loadPage);
</script>

<template>
    <Head title="Alert Rules" />

    <AppLayout
        title="Alert Rules"
        description="Manage fleet alert rules"
        active-navigation="Alert Rules"
    >
        <div class="mb-7 flex flex-col gap-4">
            <PageHeader
                eyebrow="Alert management"
                title="Alert Rules"
                description="Configure the conditions that generate fleet alerts."
                show-refresh
                @refresh="loadAlertRules()"
            />

            <div class="flex justify-end">
                <AppButton
                    v-if="!creatingAlertRule && !editingAlertRule"
                    @click="startCreatingAlertRule"
                >
                    Create alert rule
                </AppButton>
            </div>
        </div>

        <AppCard v-if="creatingAlertRule || editingAlertRule" class="mb-6">
            <div class="mb-5">
                <h3 class="text-lg font-semibold text-content">
                    {{
                        editingAlertRule
                            ? 'Edit alert rule'
                            : 'Create alert rule'
                    }}
                </h3>

                <p class="mt-1 text-sm text-muted">
                    {{
                        editingAlertRule
                            ? 'Update the alert conditions and settings.'
                            : 'Create a rule that determines when fleet alerts are generated.'
                    }}
                </p>
            </div>

            <AlertRuleForm
                :key="editingAlertRule?.id ?? 'create'"
                :alert-rule="editingAlertRule ?? undefined"
                @saved="handleAlertRuleSaved"
                @cancel="cancelAlertRuleForm"
            />
        </AppCard>

        <AppCard :padding="false">
            <LoadingState v-if="loading" message="Loading alert rules..." />

            <ErrorState
                v-else-if="error"
                title="Unable to load alert rules"
                :description="error"
                retryable
                @retry="loadPage"
            />

            <EmptyState
                v-else-if="alertRules.length === 0"
                title="No alert rules yet"
                description="Create your first alert rule to monitor fleet events."
                icon="alerts"
            />

            <template v-else>
                <AppTable>
                    <thead>
                        <tr
                            class="border-b border-border-default bg-surface-muted"
                        >
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Rule
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Vehicle
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Condition
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Severity
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Status
                            </th>

                            <th
                                class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border-default">
                        <tr
                            v-for="alertRule in alertRules"
                            :key="alertRule.id"
                            class="transition hover:bg-surface-muted"
                        >
                            <td class="px-5 py-4">
                                <div class="min-w-40">
                                    <p class="font-medium text-content">
                                        {{ alertRule.name }}
                                    </p>

                                    <p class="mt-1 text-xs text-muted">
                                        {{ typeLabel(alertRule.type) }}
                                    </p>
                                </div>
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ vehicleLabel(alertRule) }}
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ conditionsLabel(alertRule) }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <StatusBadge
                                    :variant="
                                        severityVariant(alertRule.severity)
                                    "
                                >
                                    {{ severityLabel(alertRule.severity) }}
                                </StatusBadge>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <StatusBadge
                                    :variant="
                                        alertRule.is_active
                                            ? 'success'
                                            : 'neutral'
                                    "
                                    dot
                                >
                                    {{
                                        alertRule.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </StatusBadge>
                            </td>

                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <AppButton
                                    variant="secondary"
                                    size="sm"
                                    :disabled="deletingAlertRuleId !== null"
                                    @click="startEditingAlertRule(alertRule)"
                                >
                                    Edit
                                </AppButton>

                                <AppButton
                                    variant="danger"
                                    size="sm"
                                    class="ml-2"
                                    :loading="
                                        deletingAlertRuleId === alertRule.id
                                    "
                                    :disabled="deletingAlertRuleId !== null"
                                    @click="handleDeleteAlertRule(alertRule)"
                                >
                                    Delete
                                </AppButton>
                            </td>
                        </tr>
                    </tbody>
                </AppTable>

                <AppPagination
                    v-if="alertRulesResponse"
                    :current-page="alertRulesResponse.meta.current_page"
                    :last-page="alertRulesResponse.meta.last_page"
                    :from="alertRulesResponse.meta.from"
                    :to="alertRulesResponse.meta.to"
                    :total="alertRulesResponse.meta.total"
                    item-label="alert rules"
                    @change="loadAlertRules"
                />
            </template>
        </AppCard>
    </AppLayout>
</template>
