<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

import HistoricalTrackingMap from '@/components/tracking/HistoricalTrackingMap.vue';
import LiveTrackingMap from '@/components/tracking/LiveTrackingMap.vue';
import AppButton from '@/components/ui/AppButton.vue';
import AppCard from '@/components/ui/AppCard.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ErrorState from '@/components/ui/ErrorState.vue';
import LoadingState from '@/components/ui/LoadingState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { getFleets } from '@/services/fleetService';
import {
    getLivePositions,
    getVehiclePositionHistory,
} from '@/services/trackingService';
import { getVehicles } from '@/services/vehicleService';
import type { Fleet } from '@/types/fleet';
import type { HistoricalPosition, LivePosition } from '@/types/tracking';
import type { Vehicle } from '@/types/vehicle';

const positions = ref<LivePosition[]>([]);
const fleets = ref<Fleet[]>([]);
const vehicles = ref<Vehicle[]>([]);

const selectedFleetId = ref('');
const selectedVehicleId = ref('');

const loading = ref(true);
const filtersLoading = ref(true);
const error = ref<string | null>(null);

const historyVehicleId = ref('');
const historyFrom = ref(defaultHistoryFrom());
const historyTo = ref(defaultHistoryTo());
const historyPositions = ref<HistoricalPosition[]>([]);
const selectedHistoryPosition = ref<HistoricalPosition | null>(null);
const historyLoading = ref(false);
const historyError = ref<string | null>(null);
const historyLoaded = ref(false);

const refreshInterval = 30_000;

let refreshTimer: ReturnType<typeof setInterval> | null = null;
let positionsRequestPending = false;

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

const historyVehicleOptions = computed(() => [
    {
        value: '',
        label: 'Select vehicle',
    },
    ...vehicles.value.map((vehicle) => ({
        value: vehicle.id.toString(),
        label: vehicleLabel(vehicle),
    })),
]);

const canLoadHistory = computed(() => {
    if (!historyVehicleId.value || !historyFrom.value || !historyTo.value) {
        return false;
    }

    return (
        new Date(historyFrom.value).getTime() <
        new Date(historyTo.value).getTime()
    );
});

async function loadFilters(): Promise<void> {
    filtersLoading.value = true;

    try {
        const [fleetResponse, vehicleResponse] = await Promise.all([
            getFleets(),
            getVehicles(1, 100),
        ]);

        fleets.value = fleetResponse.data;
        vehicles.value = vehicleResponse.data;
    } finally {
        filtersLoading.value = false;
    }
}

async function loadPositions(showLoading = true): Promise<void> {
    if (positionsRequestPending) {
        return;
    }

    positionsRequestPending = true;

    if (showLoading) {
        loading.value = true;
    }

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
        positionsRequestPending = false;

        if (showLoading) {
            loading.value = false;
        }
    }
}

async function loadHistory(): Promise<void> {
    if (!canLoadHistory.value) {
        return;
    }

    historyLoading.value = true;
    historyError.value = null;
    selectedHistoryPosition.value = null;

    try {
        const response = await getVehiclePositionHistory(
            Number(historyVehicleId.value),
            {
                from: toApiDateTime(historyFrom.value),
                to: toApiDateTime(historyTo.value),
            },
        );

        historyPositions.value = response.data;
        historyLoaded.value = true;
    } catch (exception) {
        historyPositions.value = [];
        historyLoaded.value = true;
        historyError.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load position history.';
    } finally {
        historyLoading.value = false;
    }
}

function startPositionPolling(): void {
    if (refreshTimer !== null) {
        return;
    }

    refreshTimer = setInterval(() => {
        void loadPositions(false);
    }, refreshInterval);
}

function stopPositionPolling(): void {
    if (refreshTimer === null) {
        return;
    }

    clearInterval(refreshTimer);
    refreshTimer = null;
}

async function handleFleetChange(): Promise<void> {
    selectedVehicleId.value = '';

    await loadPositions();
}

async function handleVehicleChange(): Promise<void> {
    await loadPositions();
}

function handleHistoryPositionSelect(position: HistoricalPosition): void {
    selectedHistoryPosition.value = position;
}

function defaultHistoryFrom(): string {
    const date = new Date();

    date.setHours(0, 0, 0, 0);

    return toLocalDateTimeInput(date);
}

function defaultHistoryTo(): string {
    return toLocalDateTimeInput(new Date());
}

function toLocalDateTimeInput(date: Date): string {
    const offset = date.getTimezoneOffset();
    const localDate = new Date(date.getTime() - offset * 60_000);

    return localDate.toISOString().slice(0, 16);
}

function toApiDateTime(value: string): string {
    return new Date(value).toISOString();
}

function vehicleLabel(vehicle: Vehicle): string {
    return `${vehicle.manufacturer} ${vehicle.model} (${vehicle.registration_number})`;
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

function formatCoordinate(value: number | null): string {
    if (value === null || !Number.isFinite(value)) {
        return '—';
    }

    return value.toFixed(6);
}

function formatNumber(value: number | null, suffix = ''): string {
    if (value === null || !Number.isFinite(value)) {
        return '—';
    }

    return `${value.toFixed(1)}${suffix}`;
}

onMounted(async () => {
    await Promise.all([loadFilters(), loadPositions()]);

    startPositionPolling();
});

onBeforeUnmount(() => {
    stopPositionPolling();
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

        <div v-else>
            <AppCard class="mb-6 overflow-hidden">
                <LiveTrackingMap :positions="positions" />
            </AppCard>

            <div class="grid gap-4 lg:grid-cols-2">
                <AppCard
                    v-for="item in positions"
                    :key="
                        item.position.id ?? item.device.id ?? item.vehicle?.id
                    "
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
        </div>

        <div class="mt-10">
            <div class="mb-5">
                <h2 class="text-xl font-semibold text-content">
                    Position history
                </h2>

                <p class="mt-1 text-sm text-muted">
                    Review the recorded route for a vehicle over a selected
                    period.
                </p>
            </div>

            <AppCard class="mb-6">
                <div class="grid gap-4 md:grid-cols-3">
                    <AppSelect
                        v-model="historyVehicleId"
                        label="Vehicle"
                        :options="historyVehicleOptions"
                        :disabled="filtersLoading"
                    />

                    <label class="block">
                        <span
                            class="mb-1.5 block text-sm font-medium text-content"
                        >
                            From
                        </span>

                        <input
                            v-model="historyFrom"
                            type="datetime-local"
                            class="focus:border-primary w-full rounded-lg border border-border-default bg-surface px-3 py-2 text-sm text-content transition outline-none"
                        />
                    </label>

                    <label class="block">
                        <span
                            class="mb-1.5 block text-sm font-medium text-content"
                        >
                            To
                        </span>

                        <input
                            v-model="historyTo"
                            type="datetime-local"
                            class="focus:border-primary w-full rounded-lg border border-border-default bg-surface px-3 py-2 text-sm text-content transition outline-none"
                        />
                    </label>
                </div>

                <div class="mt-5 flex items-center justify-between gap-4">
                    <p class="text-sm text-muted">
                        {{
                            historyLoaded
                                ? `${historyPositions.length} recorded positions`
                                : 'Select a vehicle and date range.'
                        }}
                    </p>

                    <AppButton
                        :loading="historyLoading"
                        :disabled="!canLoadHistory || historyLoading"
                        @click="loadHistory"
                    >
                        Load history
                    </AppButton>
                </div>
            </AppCard>

            <LoadingState
                v-if="historyLoading"
                message="Loading position history..."
            />

            <ErrorState
                v-else-if="historyError"
                title="Unable to load position history"
                :description="historyError"
                retryable
                @retry="loadHistory"
            />

            <EmptyState
                v-else-if="historyLoaded && historyPositions.length === 0"
                title="No position history"
                description="No recorded positions were returned for this vehicle in the selected date range."
                icon="vehicles"
            />

            <template v-else-if="historyPositions.length > 0">
                <AppCard class="overflow-hidden">
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-content">
                                Historical route
                            </h3>

                            <p class="mt-1 text-sm text-muted">
                                {{ historyPositions.length }}
                                recorded positions
                            </p>
                        </div>
                    </div>

                    <HistoricalTrackingMap
                        :positions="historyPositions"
                        @select="handleHistoryPositionSelect"
                    />
                </AppCard>

                <AppCard v-if="selectedHistoryPosition" class="mt-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-content">
                                Selected position
                            </h3>

                            <p class="mt-1 text-sm text-muted">
                                {{
                                    formatDateTime(
                                        selectedHistoryPosition.fix_time,
                                    )
                                }}
                            </p>
                        </div>

                        <AppButton
                            variant="secondary"
                            size="sm"
                            @click="selectedHistoryPosition = null"
                        >
                            Close
                        </AppButton>
                    </div>

                    <dl
                        class="mt-5 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div>
                            <dt class="text-muted">Latitude</dt>
                            <dd class="mt-1 font-medium text-content">
                                {{
                                    formatCoordinate(
                                        selectedHistoryPosition.latitude,
                                    )
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-muted">Longitude</dt>
                            <dd class="mt-1 font-medium text-content">
                                {{
                                    formatCoordinate(
                                        selectedHistoryPosition.longitude,
                                    )
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-muted">Speed</dt>
                            <dd class="mt-1 font-medium text-content">
                                {{
                                    formatNumber(
                                        selectedHistoryPosition.speed,
                                        ' kn',
                                    )
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-muted">Course</dt>
                            <dd class="mt-1 font-medium text-content">
                                {{
                                    formatNumber(
                                        selectedHistoryPosition.course,
                                        '°',
                                    )
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-muted">Accuracy</dt>
                            <dd class="mt-1 font-medium text-content">
                                {{
                                    formatNumber(
                                        selectedHistoryPosition.accuracy,
                                        ' m',
                                    )
                                }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-muted">Device time</dt>
                            <dd class="mt-1 font-medium text-content">
                                {{
                                    formatDateTime(
                                        selectedHistoryPosition.device_time,
                                    )
                                }}
                            </dd>
                        </div>
                    </dl>
                </AppCard>
            </template>
        </div>
    </AppLayout>
</template>
