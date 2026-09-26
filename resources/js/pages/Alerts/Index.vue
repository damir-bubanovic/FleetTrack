<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

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
import { acknowledgeAlert, getAlerts } from '@/services/alertService';
import { getVehicles } from '@/services/vehicleService';
import type { Alert, AlertSeverity, AlertType } from '@/types/alert';
import type { PaginatedResponse, Vehicle } from '@/types/vehicle';

const alertsResponse = ref<PaginatedResponse<Alert> | null>(null);

const vehicles = ref<Vehicle[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const acknowledgingAlertId = ref<number | null>(null);

const alerts = computed(() => alertsResponse.value?.data ?? []);

const vehicleNames = computed(() => {
    return new Map(
        vehicles.value.map((vehicle) => [
            vehicle.id,
            `${vehicle.manufacturer} ${vehicle.model} (${vehicle.registration_number})`,
        ]),
    );
});

async function loadAlerts(page = 1): Promise<void> {
    if (page < 1) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        alertsResponse.value = await getAlerts(page);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load alerts.';
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

async function handleAcknowledge(alert: Alert): Promise<void> {
    if (alert.acknowledged_at !== null) {
        return;
    }

    acknowledgingAlertId.value = alert.id;
    error.value = null;

    try {
        await acknowledgeAlert(alert);

        await loadAlerts(alertsResponse.value?.meta.current_page ?? 1);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to acknowledge alert.';
    } finally {
        acknowledgingAlertId.value = null;
    }
}

function typeLabel(type: AlertType): string {
    const labels: Record<AlertType, string> = {
        overspeed: 'Overspeed',
        geofence_enter: 'Geofence enter',
        geofence_exit: 'Geofence exit',
        ignition_on: 'Ignition on',
        ignition_off: 'Ignition off',
        device_offline: 'Device offline',
    };

    return labels[type];
}

function severityLabel(severity: AlertSeverity): string {
    return severity.charAt(0).toUpperCase() + severity.slice(1);
}

function severityVariant(
    severity: AlertSeverity,
): 'info' | 'warning' | 'danger' {
    if (severity === 'critical') {
        return 'danger';
    }

    if (severity === 'warning') {
        return 'warning';
    }

    return 'info';
}

function vehicleLabel(alert: Alert): string {
    if (alert.vehicle_id === null) {
        return '—';
    }

    return (
        vehicleNames.value.get(alert.vehicle_id) ??
        `Vehicle #${alert.vehicle_id}`
    );
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

async function loadPage(): Promise<void> {
    await Promise.all([loadAlerts(), loadVehicles()]);
}

onMounted(loadPage);
</script>

<template>
    <Head title="Alerts" />

    <AppLayout
        title="Alerts"
        description="Monitor fleet alerts"
        active-navigation="Alerts"
    >
        <div class="mb-7">
            <PageHeader
                eyebrow="Fleet monitoring"
                title="Alerts"
                description="Review fleet events and acknowledge alerts that have been handled."
                show-refresh
                @refresh="loadAlerts()"
            />
        </div>

        <AppCard :padding="false">
            <LoadingState v-if="loading" message="Loading alerts..." />

            <ErrorState
                v-else-if="error"
                title="Unable to load alerts"
                :description="error"
                retryable
                @retry="loadPage"
            />

            <EmptyState
                v-else-if="alerts.length === 0"
                title="No alerts"
                description="Fleet alerts will appear here when configured alert rules are triggered."
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
                                Alert
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Vehicle
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Severity
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Occurred
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
                            v-for="alert in alerts"
                            :key="alert.id"
                            class="transition hover:bg-surface-muted"
                        >
                            <td class="px-5 py-4">
                                <div class="max-w-md">
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <p class="font-medium text-content">
                                            {{ alert.title }}
                                        </p>

                                        <span class="text-xs text-muted">
                                            {{ typeLabel(alert.type) }}
                                        </span>
                                    </div>

                                    <p
                                        class="mt-1 text-sm text-content-secondary"
                                    >
                                        {{ alert.message }}
                                    </p>
                                </div>
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ vehicleLabel(alert) }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <StatusBadge
                                    :variant="severityVariant(alert.severity)"
                                >
                                    {{ severityLabel(alert.severity) }}
                                </StatusBadge>
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDateTime(alert.occurred_at) }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <StatusBadge
                                    :variant="
                                        alert.acknowledged_at
                                            ? 'success'
                                            : 'warning'
                                    "
                                    dot
                                >
                                    {{
                                        alert.acknowledged_at
                                            ? 'Acknowledged'
                                            : 'Open'
                                    }}
                                </StatusBadge>

                                <p
                                    v-if="alert.acknowledged_at"
                                    class="mt-1 text-xs text-muted"
                                >
                                    {{ formatDateTime(alert.acknowledged_at) }}
                                </p>
                            </td>

                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <AppButton
                                    v-if="!alert.acknowledged_at"
                                    variant="secondary"
                                    size="sm"
                                    :loading="acknowledgingAlertId === alert.id"
                                    :disabled="acknowledgingAlertId !== null"
                                    @click="handleAcknowledge(alert)"
                                >
                                    Acknowledge
                                </AppButton>

                                <span v-else class="text-sm text-muted">
                                    Handled
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </AppTable>

                <AppPagination
                    v-if="alertsResponse"
                    :current-page="alertsResponse.meta.current_page"
                    :last-page="alertsResponse.meta.last_page"
                    :from="alertsResponse.meta.from"
                    :to="alertsResponse.meta.to"
                    :total="alertsResponse.meta.total"
                    item-label="alerts"
                    @change="loadAlerts"
                />
            </template>
        </AppCard>
    </AppLayout>
</template>
