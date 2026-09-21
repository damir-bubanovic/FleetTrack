<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import AppCard from '@/components/ui/AppCard.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ErrorState from '@/components/ui/ErrorState.vue';
import LoadingState from '@/components/ui/LoadingState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { getFleets } from '@/services/fleetService';
import { getLivePositions } from '@/services/trackingService';
import { getVehicles } from '@/services/vehicleService';
import type { Fleet } from '@/types/fleet';
import type { LivePosition } from '@/types/tracking';
import type { Vehicle } from '@/types/vehicle';

const positions = ref<LivePosition[]>([]);
const fleets = ref<Fleet[]>([]);
const vehicles = ref<Vehicle[]>([]);

const selectedFleetId = ref('');
const selectedVehicleId = ref('');

const loading = ref(true);
const filtersLoading = ref(true);
const error = ref<string | null>(null);

const onlineCount = computed(
    () => positions.value.filter((item) => item.status.online).length,
);

const offlineCount = computed(
    () => positions.value.filter((item) => !item.status.online).length,
);

const fleetOptions = computed(() => [
    {
        value: '',
        label: 'All fleets',
    },
    ...fleets.value.map((fleet) => ({
        value: fleet.id.toString(),
        label: fleet.name,
    })),
]);

const vehicleOptions = computed(() => [
    {
        value: '',
        label: 'All vehicles',
    },
    ...vehicles.value
        .filter((vehicle) => {
            if (!selectedFleetId.value) {
                return true;
            }

            return vehicle.fleet_id === Number(selectedFleetId.value);
        })
        .map((vehicle) => ({
            value: vehicle.id.toString(),
            label: vehicle.registration_number,
        })),
]);

async function loadFilters(): Promise<void> {
    filtersLoading.value = true;

    try {
        const [fleetResponse, vehicleResponse] = await Promise.all([
            getFleets(),
            getVehicles(),
        ]);

        fleets.value = fleetResponse.data;
        vehicles.value = vehicleResponse.data;
    } finally {
        filtersLoading.value = false;
    }
}

async function loadPositions(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
        const response = await getLivePositions({
            fleetId: selectedFleetId.value
                ? Number(selectedFleetId.value)
                : undefined,
            vehicleId: selectedVehicleId.value
                ? Number(selectedVehicleId.value)
                : undefined,
        });

        positions.value = response.data;
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load live positions.';
    } finally {
        loading.value = false;
    }
}

async function handleFleetChange(): Promise<void> {
    selectedVehicleId.value = '';

    await loadPositions();
}

async function handleVehicleChange(): Promise<void> {
    await loadPositions();
}

function formatDateTime(value: string | null): string {
    if (!value) {
        return 'Never';
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

onMounted(async () => {
    await Promise.all([loadFilters(), loadPositions()]);
});
</script>

<template>
    <Head title="Live Tracking" />

    <AppLayout
        title="Live Tracking"
        description="Monitor your fleet in real time"
        active-navigation="Live Tracking"
    >
        <div class="mb-7">
            <PageHeader
                eyebrow="Fleet tracking"
                title="Live Tracking"
                description="Monitor the latest reported positions and status of your vehicles."
                show-refresh
                @refresh="loadPositions"
            />
        </div>

        <AppCard class="mb-6">
            <div class="grid gap-4 md:grid-cols-2">
                <AppSelect
                    v-model="selectedFleetId"
                    label="Fleet"
                    :options="fleetOptions"
                    :disabled="filtersLoading"
                    @change="handleFleetChange"
                />

                <AppSelect
                    v-model="selectedVehicleId"
                    label="Vehicle"
                    :options="vehicleOptions"
                    :disabled="filtersLoading"
                    @change="handleVehicleChange"
                />
            </div>
        </AppCard>

        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <AppCard>
                <p class="text-sm font-medium text-muted">Vehicles</p>

                <p class="mt-2 text-2xl font-semibold text-content">
                    {{ positions.length }}
                </p>
            </AppCard>

            <AppCard>
                <p class="text-sm font-medium text-muted">Online</p>

                <p class="mt-2 text-2xl font-semibold text-content">
                    {{ onlineCount }}
                </p>
            </AppCard>

            <AppCard>
                <p class="text-sm font-medium text-muted">Offline</p>

                <p class="mt-2 text-2xl font-semibold text-content">
                    {{ offlineCount }}
                </p>
            </AppCard>
        </div>

        <LoadingState v-if="loading" message="Loading live positions..." />

        <ErrorState
            v-else-if="error"
            title="Unable to load live positions"
            :description="error"
            retryable
            @retry="loadPositions"
        />

        <EmptyState
            v-else-if="positions.length === 0"
            title="No live positions"
            description="No tracked vehicles currently have position data available."
            icon="vehicles"
        />

        <div v-else class="grid gap-4 lg:grid-cols-2">
            <AppCard
                v-for="item in positions"
                :key="item.position.id ?? item.device.id ?? item.vehicle?.id"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-content">
                            {{
                                item.vehicle?.name ??
                                item.device.name ??
                                'Vehicle'
                            }}
                        </h3>

                        <p class="mt-1 text-sm text-muted">
                            {{
                                item.device.name ??
                                item.device.unique_id ??
                                'Unknown device'
                            }}
                        </p>
                    </div>

                    <StatusBadge
                        :variant="item.status.online ? 'success' : 'danger'"
                    >
                        {{ item.status.online ? 'Online' : 'Offline' }}
                    </StatusBadge>
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-muted">Latitude</dt>
                        <dd class="mt-1 font-medium text-content">
                            {{ item.position.latitude ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-muted">Longitude</dt>
                        <dd class="mt-1 font-medium text-content">
                            {{ item.position.longitude ?? '—' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-muted">Speed</dt>
                        <dd class="mt-1 font-medium text-content">
                            {{
                                item.position.speed !== null
                                    ? `${item.position.speed} kn`
                                    : '—'
                            }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-muted">Last seen</dt>
                        <dd class="mt-1 font-medium text-content">
                            {{ formatDateTime(item.status.last_seen_at) }}
                        </dd>
                    </div>
                </dl>
            </AppCard>
        </div>
    </AppLayout>
</template>
