<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import DashboardMetricCard from '@/components/dashboard/DashboardMetricCard.vue';
import AppCard from '@/components/ui/AppCard.vue';
import ErrorState from '@/components/ui/ErrorState.vue';
import LoadingState from '@/components/ui/LoadingState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { getDashboardOverview } from '@/services/dashboardService';
import type { DashboardOverview } from '@/types/dashboard';

const overview = ref<DashboardOverview | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const fleetMetrics = computed(() => {
    if (!overview.value) {
        return [];
    }

    return [
        {
            label: 'Vehicles',
            value: overview.value.vehicles,
            detail: 'Fleet vehicles',
            icon: 'vehicles',
        },
        {
            label: 'Online vehicles',
            value: overview.value.online_vehicles,
            detail: 'Currently reporting',
            icon: 'online',
        },
        {
            label: 'Offline vehicles',
            value: overview.value.offline_vehicles,
            detail: 'Not currently reporting',
            icon: 'offline',
        },
        {
            label: 'Open alerts',
            value: overview.value.unacknowledged_alerts,
            detail: `${overview.value.alerts} total alerts`,
            icon: 'alerts',
        },
    ] as const;
});

const infrastructureMetrics = computed(() => {
    if (!overview.value) {
        return [];
    }

    return [
        {
            label: 'Companies',
            value: overview.value.companies,
        },
        {
            label: 'Fleets',
            value: overview.value.fleets,
        },
        {
            label: 'Devices',
            value: overview.value.devices,
        },
        {
            label: 'Offline devices',
            value: overview.value.offline_devices,
        },
    ];
});

async function loadDashboard(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        overview.value = await getDashboardOverview();
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load dashboard overview.';
    } finally {
        loading.value = false;
    }
}

onMounted(loadDashboard);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout
        title="Dashboard"
        description="Fleet operations overview"
        active-navigation="Dashboard"
    >
        <PageHeader
            eyebrow="Operations"
            title="Fleet overview"
            description="Current status of your vehicles, devices, fleets, and alerts."
            show-refresh
            @refresh="loadDashboard"
        />

        <div class="mt-6">
            <LoadingState v-if="loading" message="Loading dashboard..." />

            <ErrorState
                v-else-if="error"
                title="Unable to load dashboard"
                :description="error"
                retryable
                @retry="loadDashboard"
            />

            <template v-else>
                <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <DashboardMetricCard
                        v-for="metric in fleetMetrics"
                        :key="metric.label"
                        :label="metric.label"
                        :value="metric.value"
                        :detail="metric.detail"
                        :icon="metric.icon"
                    />
                </section>

                <AppCard class="mt-6">
                    <div class="mb-5">
                        <h2 class="font-semibold text-content">
                            Fleet infrastructure
                        </h2>

                        <p class="mt-1 text-sm text-muted">
                            Companies, fleets, and tracking device totals.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="metric in infrastructureMetrics"
                            :key="metric.label"
                            class="rounded-lg border border-border-default bg-surface-muted p-4"
                        >
                            <p class="text-sm text-muted">
                                {{ metric.label }}
                            </p>

                            <p class="mt-2 text-2xl font-semibold text-content">
                                {{ metric.value }}
                            </p>
                        </div>
                    </div>
                </AppCard>
            </template>
        </div>
    </AppLayout>
</template>
