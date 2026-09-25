<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import AppButton from '@/components/ui/AppButton.vue';
import AppCard from '@/components/ui/AppCard.vue';
import AppTable from '@/components/ui/AppTable.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ErrorState from '@/components/ui/ErrorState.vue';
import LoadingState from '@/components/ui/LoadingState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    getVehicleCombinedReport,
    getVehicleEvents,
    getVehicleHours,
    getVehicleRoute,
    getVehicleStops,
    getVehicleSummary,
    getVehicleTrips,
    getVehicleTripSummary,
} from '@/services/reportService';
import { getVehicles } from '@/services/vehicleService';
import type {
    ReportDateRange,
    VehicleCombinedReport,
    VehicleEvent,
    VehicleHours,
    VehicleRoutePosition,
    VehicleStop,
    VehicleSummary,
    VehicleTrip,
    VehicleTripSummary,
} from '@/types/report';
import type { Vehicle } from '@/types/vehicle';

const vehicles = ref<Vehicle[]>([]);
const selectedVehicleId = ref<number | null>(null);

const from = ref(defaultFrom());
const to = ref(defaultTo());

const trips = ref<VehicleTrip[]>([]);
const stops = ref<VehicleStop[]>([]);
const tripSummary = ref<VehicleTripSummary | null>(null);
const events = ref<VehicleEvent[]>([]);
const routePositions = ref<VehicleRoutePosition[]>([]);
const vehicleSummary = ref<VehicleSummary[]>([]);
const vehicleHours = ref<VehicleHours[]>([]);
const combinedReport = ref<VehicleCombinedReport[]>([]);

const loadingVehicles = ref(true);
const loadingReport = ref(false);
const error = ref<string | null>(null);
const hasGeneratedReport = ref(false);

const selectedVehicle = computed(() => {
    if (selectedVehicleId.value === null) {
        return null;
    }

    return (
        vehicles.value.find(
            (vehicle) => vehicle.id === selectedVehicleId.value,
        ) ?? null
    );
});

const canGenerate = computed(() => {
    return (
        selectedVehicleId.value !== null &&
        from.value !== '' &&
        to.value !== '' &&
        new Date(from.value).getTime() < new Date(to.value).getTime()
    );
});

function defaultFrom(): string {
    const date = new Date();

    date.setHours(0, 0, 0, 0);

    return toLocalDateTimeInput(date);
}

function defaultTo(): string {
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

async function loadVehicles(): Promise<void> {
    loadingVehicles.value = true;
    error.value = null;

    try {
        const response = await getVehicles(1, 100);

        vehicles.value = response.data;

        if (selectedVehicleId.value === null && vehicles.value.length > 0) {
            selectedVehicleId.value = vehicles.value[0].id;
        }
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load vehicles.';
    } finally {
        loadingVehicles.value = false;
    }
}

async function generateReport(): Promise<void> {
    if (!canGenerate.value || selectedVehicleId.value === null) {
        return;
    }

    loadingReport.value = true;
    error.value = null;

    const range: ReportDateRange = {
        from: toApiDateTime(from.value),
        to: toApiDateTime(to.value),
    };

    try {
        const [
            tripsResponse,
            tripSummaryResponse,
            stopsResponse,
            eventsResponse,
            routeResponse,
            summaryResponse,
            hoursResponse,
            combinedResponse,
        ] = await Promise.all([
            getVehicleTrips(selectedVehicleId.value, range),
            getVehicleTripSummary(selectedVehicleId.value, range),
            getVehicleStops(selectedVehicleId.value, range),
            getVehicleEvents(selectedVehicleId.value, range),
            getVehicleRoute(selectedVehicleId.value, range),
            getVehicleSummary(selectedVehicleId.value, range),
            getVehicleHours(selectedVehicleId.value, range),
            getVehicleCombinedReport(selectedVehicleId.value, range),
        ]);

        trips.value = tripsResponse;
        tripSummary.value = tripSummaryResponse;
        stops.value = stopsResponse;
        events.value = eventsResponse;
        routePositions.value = routeResponse;
        vehicleSummary.value = summaryResponse;
        vehicleHours.value = hoursResponse;
        combinedReport.value = combinedResponse;

        hasGeneratedReport.value = true;
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to generate report.';
    } finally {
        loadingReport.value = false;
    }
}

function vehicleLabel(vehicle: Vehicle): string {
    return `${vehicle.manufacturer} ${vehicle.model} (${vehicle.registration_number})`;
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

function formatDuration(seconds: number | null): string {
    if (seconds === null || !Number.isFinite(seconds)) {
        return '—';
    }

    const totalSeconds = Math.max(0, Math.round(seconds));
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }

    return `${minutes}m`;
}

function formatDistance(value: number | null): string {
    if (value === null || !Number.isFinite(value)) {
        return '—';
    }

    return `${value.toFixed(2)} km`;
}

function formatSpeed(value: number | null): string {
    if (value === null || !Number.isFinite(value)) {
        return '—';
    }

    return `${value.toFixed(1)} kn`;
}

function formatNumber(value: number | null): string {
    if (value === null || !Number.isFinite(value)) {
        return '—';
    }

    return new Intl.NumberFormat(undefined, {
        maximumFractionDigits: 2,
    }).format(value);
}

function formatCoordinate(value: number | null): string {
    if (value === null || !Number.isFinite(value)) {
        return '—';
    }

    return value.toFixed(6);
}

function formatCombinedReport(report: VehicleCombinedReport): string {
    return JSON.stringify(report, null, 2);
}

onMounted(loadVehicles);
</script>

<template>
    <Head title="Reports" />

    <AppLayout
        title="Reports"
        description="Fleet activity reports"
        active-navigation="Reports"
    >
        <div class="mb-7">
            <PageHeader
                eyebrow="Fleet analytics"
                title="Reports"
                description="Review vehicle trips, movement summaries, and stops for a selected date range."
            />
        </div>

        <AppCard class="mb-6">
            <div
                class="grid gap-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto]"
            >
                <div>
                    <label
                        for="report-vehicle"
                        class="mb-2 block text-sm font-medium text-content"
                    >
                        Vehicle
                    </label>

                    <select
                        id="report-vehicle"
                        v-model="selectedVehicleId"
                        :disabled="loadingVehicles"
                        class="focus:border-primary h-10 w-full rounded-lg border border-border-default bg-surface px-3 text-sm text-content transition outline-none"
                    >
                        <option :value="null" disabled>Select vehicle</option>

                        <option
                            v-for="vehicle in vehicles"
                            :key="vehicle.id"
                            :value="vehicle.id"
                        >
                            {{ vehicleLabel(vehicle) }}
                        </option>
                    </select>
                </div>

                <div>
                    <label
                        for="report-from"
                        class="mb-2 block text-sm font-medium text-content"
                    >
                        From
                    </label>

                    <input
                        id="report-from"
                        v-model="from"
                        type="datetime-local"
                        class="focus:border-primary h-10 w-full rounded-lg border border-border-default bg-surface px-3 text-sm text-content transition outline-none"
                    />
                </div>

                <div>
                    <label
                        for="report-to"
                        class="mb-2 block text-sm font-medium text-content"
                    >
                        To
                    </label>

                    <input
                        id="report-to"
                        v-model="to"
                        type="datetime-local"
                        class="focus:border-primary h-10 w-full rounded-lg border border-border-default bg-surface px-3 text-sm text-content transition outline-none"
                    />
                </div>

                <div class="flex items-end">
                    <AppButton
                        :disabled="!canGenerate"
                        :loading="loadingReport"
                        @click="generateReport"
                    >
                        Generate report
                    </AppButton>
                </div>
            </div>

            <p
                v-if="from && to && !canGenerate"
                class="mt-3 text-sm text-danger"
            >
                The end date must be after the start date.
            </p>
        </AppCard>

        <LoadingState
            v-if="loadingVehicles || loadingReport"
            message="Loading report..."
        />

        <ErrorState
            v-else-if="error"
            title="Unable to generate report"
            :description="error"
            retryable
            @retry="generateReport"
        />

        <EmptyState
            v-else-if="!hasGeneratedReport"
            title="Generate a vehicle report"
            description="Select a vehicle and date range to review its fleet activity."
            icon="reports"
        />

        <template v-else>
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-content">
                    {{
                        selectedVehicle
                            ? vehicleLabel(selectedVehicle)
                            : 'Vehicle report'
                    }}
                </h2>

                <p class="mt-1 text-sm text-muted">
                    {{ formatDateTime(toApiDateTime(from)) }}
                    –
                    {{ formatDateTime(toApiDateTime(to)) }}
                </p>
            </div>

            <div
                v-if="tripSummary"
                class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
            >
                <AppCard>
                    <p class="text-sm text-muted">Distance</p>
                    <p class="mt-2 text-2xl font-semibold text-content">
                        {{ formatDistance(tripSummary.distance_km) }}
                    </p>
                </AppCard>

                <AppCard>
                    <p class="text-sm text-muted">Duration</p>
                    <p class="mt-2 text-2xl font-semibold text-content">
                        {{ formatDuration(tripSummary.duration_seconds) }}
                    </p>
                </AppCard>

                <AppCard>
                    <p class="text-sm text-muted">Average speed</p>
                    <p class="mt-2 text-2xl font-semibold text-content">
                        {{ formatSpeed(tripSummary.average_speed) }}
                    </p>
                </AppCard>

                <AppCard>
                    <p class="text-sm text-muted">Maximum speed</p>
                    <p class="mt-2 text-2xl font-semibold text-content">
                        {{ formatSpeed(tripSummary.max_speed) }}
                    </p>
                </AppCard>
            </div>

            <AppCard :padding="false" class="mb-6">
                <div class="border-b border-border-default px-5 py-4">
                    <h2 class="font-semibold text-content">Trips</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ trips.length }} recorded trips
                    </p>
                </div>

                <EmptyState
                    v-if="trips.length === 0"
                    title="No trips"
                    description="No trips were recorded for this vehicle in the selected date range."
                    icon="reports"
                />

                <AppTable v-else>
                    <thead>
                        <tr
                            class="border-b border-border-default bg-surface-muted"
                        >
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Started
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Ended
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Distance
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Duration
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Avg. speed
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Max speed
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border-default">
                        <tr
                            v-for="(trip, index) in trips"
                            :key="`${trip.started_at}-${index}`"
                            class="transition hover:bg-surface-muted"
                        >
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDateTime(trip.started_at) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDateTime(trip.ended_at) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDistance(trip.distance_km) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDuration(trip.duration_seconds) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatSpeed(trip.average_speed) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatSpeed(trip.max_speed) }}
                            </td>
                        </tr>
                    </tbody>
                </AppTable>
            </AppCard>

            <AppCard :padding="false">
                <div class="border-b border-border-default px-5 py-4">
                    <h2 class="font-semibold text-content">Stops</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ stops.length }} recorded stops
                    </p>
                </div>

                <EmptyState
                    v-if="stops.length === 0"
                    title="No stops"
                    description="No stops were recorded for this vehicle in the selected date range."
                    icon="reports"
                />

                <AppTable v-else>
                    <thead>
                        <tr
                            class="border-b border-border-default bg-surface-muted"
                        >
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Started
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Ended
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Duration
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Address
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border-default">
                        <tr
                            v-for="(stop, index) in stops"
                            :key="`${stop.start_time}-${index}`"
                            class="transition hover:bg-surface-muted"
                        >
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDateTime(stop.start_time) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDateTime(stop.end_time) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDuration(stop.duration) }}
                            </td>
                            <td class="px-5 py-4 text-content-secondary">
                                {{ stop.address ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </AppTable>
            </AppCard>

            <AppCard :padding="false" class="mt-6">
                <div class="border-b border-border-default px-5 py-4">
                    <h2 class="font-semibold text-content">Vehicle summary</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ vehicleSummary.length }} summary records
                    </p>
                </div>

                <EmptyState
                    v-if="vehicleSummary.length === 0"
                    title="No vehicle summary"
                    description="No summary data was returned for this vehicle in the selected date range."
                    icon="reports"
                />

                <AppTable v-else>
                    <thead>
                        <tr
                            class="border-b border-border-default bg-surface-muted"
                        >
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Device
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Distance
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Avg. speed
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Max speed
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Fuel
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Start odometer
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                End odometer
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default">
                        <tr
                            v-for="(summary, index) in vehicleSummary"
                            :key="`${summary.device_id}-${index}`"
                            class="transition hover:bg-surface-muted"
                        >
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{
                                    summary.device_name ??
                                    (summary.device_id !== null
                                        ? `#${summary.device_id}`
                                        : '—')
                                }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatNumber(summary.distance) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatNumber(summary.average_speed) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatNumber(summary.max_speed) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatNumber(summary.spent_fuel) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatNumber(summary.start_odometer) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatNumber(summary.end_odometer) }}
                            </td>
                        </tr>
                    </tbody>
                </AppTable>
            </AppCard>

            <AppCard :padding="false" class="mt-6">
                <div class="border-b border-border-default px-5 py-4">
                    <h2 class="font-semibold text-content">Hours</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ vehicleHours.length }} hours records
                    </p>
                </div>
                <EmptyState
                    v-if="vehicleHours.length === 0"
                    title="No hours data"
                    description="No hours data was returned for this vehicle in the selected date range."
                    icon="reports"
                />
                <AppTable v-else>
                    <thead>
                        <tr
                            class="border-b border-border-default bg-surface-muted"
                        >
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Device
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Hours
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default">
                        <tr
                            v-for="(hours, index) in vehicleHours"
                            :key="`${hours.device_id}-${index}`"
                            class="transition hover:bg-surface-muted"
                        >
                            <td class="px-5 py-4 text-content-secondary">
                                {{
                                    hours.device_name ??
                                    (hours.device_id !== null
                                        ? `#${hours.device_id}`
                                        : '—')
                                }}
                            </td>
                            <td class="px-5 py-4 text-content-secondary">
                                {{ formatNumber(hours.hours) }}
                            </td>
                        </tr>
                    </tbody>
                </AppTable>
            </AppCard>

            <AppCard :padding="false" class="mt-6">
                <div class="border-b border-border-default px-5 py-4">
                    <h2 class="font-semibold text-content">Events</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ events.length }} recorded events
                    </p>
                </div>
                <EmptyState
                    v-if="events.length === 0"
                    title="No events"
                    description="No events were recorded for this vehicle in the selected date range."
                    icon="reports"
                />
                <AppTable v-else>
                    <thead>
                        <tr
                            class="border-b border-border-default bg-surface-muted"
                        >
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Time
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Type
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Position
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Geofence
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default">
                        <tr
                            v-for="(event, index) in events"
                            :key="event.id ?? index"
                            class="transition hover:bg-surface-muted"
                        >
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDateTime(event.event_time) }}
                            </td>
                            <td class="px-5 py-4 text-content-secondary">
                                {{ event.type ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-content-secondary">
                                {{ event.position_id ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-content-secondary">
                                {{ event.geofence_id ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </AppTable>
            </AppCard>

            <AppCard :padding="false" class="mt-6">
                <div class="border-b border-border-default px-5 py-4">
                    <h2 class="font-semibold text-content">Route</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ routePositions.length }} recorded positions
                    </p>
                </div>
                <EmptyState
                    v-if="routePositions.length === 0"
                    title="No route positions"
                    description="No route positions were recorded for this vehicle in the selected date range."
                    icon="reports"
                />
                <AppTable v-else>
                    <thead>
                        <tr
                            class="border-b border-border-default bg-surface-muted"
                        >
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Time
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Latitude
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Longitude
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Speed
                            </th>
                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Address
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-default">
                        <tr
                            v-for="(position, index) in routePositions"
                            :key="position.id ?? index"
                            class="transition hover:bg-surface-muted"
                        >
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{
                                    formatDateTime(
                                        position.fix_time ??
                                            position.device_time,
                                    )
                                }}
                            </td>
                            <td
                                class="px-5 py-4 font-mono text-xs whitespace-nowrap text-content-secondary"
                            >
                                {{ formatCoordinate(position.latitude) }}
                            </td>
                            <td
                                class="px-5 py-4 font-mono text-xs whitespace-nowrap text-content-secondary"
                            >
                                {{ formatCoordinate(position.longitude) }}
                            </td>
                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatSpeed(position.speed) }}
                            </td>
                            <td class="px-5 py-4 text-content-secondary">
                                {{ position.address ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </AppTable>
            </AppCard>

            <AppCard :padding="false" class="mt-6">
                <div class="border-b border-border-default px-5 py-4">
                    <h2 class="font-semibold text-content">Combined report</h2>
                    <p class="mt-1 text-sm text-muted">
                        {{ combinedReport.length }} combined records
                    </p>
                </div>
                <EmptyState
                    v-if="combinedReport.length === 0"
                    title="No combined report data"
                    description="No combined report data was returned for this vehicle in the selected date range."
                    icon="reports"
                />
                <div v-else class="space-y-4 p-5">
                    <pre
                        v-for="(report, index) in combinedReport"
                        :key="index"
                        class="overflow-x-auto rounded-lg bg-surface-muted p-4 text-xs text-content-secondary"
                        >{{ formatCombinedReport(report) }}</pre>
                </div>
            </AppCard>
        </template>
    </AppLayout>
</template>
