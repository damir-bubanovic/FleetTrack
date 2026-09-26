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
import VehicleForm from '@/components/vehicles/VehicleForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { getFleets } from '@/services/fleetService';
import { deleteVehicle, getVehicles } from '@/services/vehicleService';
import type { Fleet } from '@/types/fleet';
import type { PaginatedResponse, Vehicle } from '@/types/vehicle';

const vehiclesResponse = ref<PaginatedResponse<Vehicle> | null>(null);
const fleets = ref<Fleet[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creatingVehicle = ref(false);
const editingVehicle = ref<Vehicle | null>(null);
const deletingVehicleId = ref<number | null>(null);

const vehicles = computed(() => vehiclesResponse.value?.data ?? []);

const fleetNames = computed(() => {
    return new Map(fleets.value.map((fleet) => [fleet.id, fleet.name]));
});

async function loadVehicles(page = 1): Promise<void> {
    if (page < 1) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        vehiclesResponse.value = await getVehicles(page);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load vehicles.';
    } finally {
        loading.value = false;
    }
}

async function loadFleets(): Promise<void> {
    try {
        const response = await getFleets();

        fleets.value = response.data;
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load fleets.';
    }
}

function startCreatingVehicle(): void {
    if (fleets.value.length === 0) {
        return;
    }

    editingVehicle.value = null;
    creatingVehicle.value = true;
}

function startEditingVehicle(vehicle: Vehicle): void {
    creatingVehicle.value = false;
    editingVehicle.value = vehicle;
}

function cancelVehicleForm(): void {
    creatingVehicle.value = false;
    editingVehicle.value = null;
}

async function handleVehicleSaved(): Promise<void> {
    cancelVehicleForm();

    await loadVehicles(1);
}

async function handleDeleteVehicle(vehicle: Vehicle): Promise<void> {
    const confirmed = window.confirm(
        `Delete "${vehicle.manufacturer} ${vehicle.model}" (${vehicle.registration_number})? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    deletingVehicleId.value = vehicle.id;
    error.value = null;

    try {
        await deleteVehicle(vehicle);

        if (editingVehicle.value?.id === vehicle.id) {
            cancelVehicleForm();
        }

        await loadVehicles(vehiclesResponse.value?.meta.current_page ?? 1);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to delete vehicle.';
    } finally {
        deletingVehicleId.value = null;
    }
}

async function loadPage(): Promise<void> {
    await Promise.all([loadVehicles(), loadFleets()]);
}

onMounted(loadPage);
</script>

<template>
    <Head title="Vehicles" />

    <AppLayout
        title="Vehicles"
        description="Manage fleet vehicles"
        active-navigation="Vehicles"
    >
        <div class="mb-7 flex flex-col gap-4">
            <PageHeader
                eyebrow="Vehicle management"
                title="Vehicles"
                description="Manage vehicles across your fleets."
                show-refresh
                @refresh="loadVehicles()"
            />

            <div class="flex justify-end">
                <AppButton
                    v-if="!creatingVehicle && !editingVehicle"
                    :disabled="fleets.length === 0"
                    @click="startCreatingVehicle"
                >
                    Create vehicle
                </AppButton>
            </div>
        </div>

        <AppCard v-if="creatingVehicle || editingVehicle" class="mb-6">
            <div class="mb-5">
                <h3 class="text-lg font-semibold text-content">
                    {{ editingVehicle ? 'Edit vehicle' : 'Create vehicle' }}
                </h3>

                <p class="mt-1 text-sm text-muted">
                    {{
                        editingVehicle
                            ? 'Update the vehicle information and settings.'
                            : 'Add a new vehicle to one of your fleets.'
                    }}
                </p>
            </div>

            <VehicleForm
                :key="editingVehicle?.id ?? 'create'"
                :vehicle="editingVehicle ?? undefined"
                :fleets="fleets"
                @saved="handleVehicleSaved"
                @cancel="cancelVehicleForm"
            />
        </AppCard>

        <AppCard :padding="false">
            <LoadingState v-if="loading" message="Loading vehicles..." />

            <ErrorState
                v-else-if="error"
                title="Unable to load vehicles"
                :description="error"
                retryable
                @retry="loadPage"
            />

            <EmptyState
                v-else-if="vehicles.length === 0"
                title="No vehicles yet"
                description="Create your first vehicle to start tracking your fleet."
                icon="vehicles"
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
                                Vehicle
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Registration
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Fleet
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Vehicle details
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
                            v-for="vehicle in vehicles"
                            :key="vehicle.id"
                            class="transition hover:bg-surface-muted"
                        >
                            <td class="px-5 py-4">
                                <div class="min-w-44">
                                    <p class="font-medium text-content">
                                        {{ vehicle.manufacturer }}
                                        {{ vehicle.model }}
                                    </p>

                                    <p class="mt-1 text-xs text-muted">
                                        VIN: {{ vehicle.vin }}
                                    </p>
                                </div>
                            </td>

                            <td
                                class="px-5 py-4 font-medium whitespace-nowrap text-content-secondary"
                            >
                                {{ vehicle.registration_number }}
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ fleetNames.get(vehicle.fleet_id) ?? '—' }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="min-w-36">
                                    <p class="text-content-secondary">
                                        {{ vehicle.year }}

                                        <span v-if="vehicle.color">
                                            · {{ vehicle.color }}
                                        </span>
                                    </p>

                                    <p class="mt-1 text-xs text-muted">
                                        {{ vehicle.fuel_type }}
                                        ·
                                        {{ vehicle.transmission }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <StatusBadge
                                    :variant="
                                        vehicle.is_active
                                            ? 'success'
                                            : 'neutral'
                                    "
                                    dot
                                >
                                    {{
                                        vehicle.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </StatusBadge>
                            </td>

                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <AppButton
                                    variant="secondary"
                                    size="sm"
                                    :disabled="deletingVehicleId !== null"
                                    @click="startEditingVehicle(vehicle)"
                                >
                                    Edit
                                </AppButton>

                                <AppButton
                                    variant="danger"
                                    size="sm"
                                    class="ml-2"
                                    :loading="deletingVehicleId === vehicle.id"
                                    :disabled="deletingVehicleId !== null"
                                    @click="handleDeleteVehicle(vehicle)"
                                >
                                    Delete
                                </AppButton>
                            </td>
                        </tr>
                    </tbody>
                </AppTable>

                <AppPagination
                    v-if="vehiclesResponse"
                    :current-page="vehiclesResponse.meta.current_page"
                    :last-page="vehiclesResponse.meta.last_page"
                    :from="vehiclesResponse.meta.from"
                    :to="vehiclesResponse.meta.to"
                    :total="vehiclesResponse.meta.total"
                    item-label="vehicles"
                    @change="loadVehicles"
                />
            </template>
        </AppCard>
    </AppLayout>
</template>
