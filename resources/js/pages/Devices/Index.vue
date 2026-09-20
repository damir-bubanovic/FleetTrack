<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import DeviceForm from '@/components/devices/DeviceForm.vue';
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
import { deleteDevice, getDevices } from '@/services/deviceService';
import { getVehicles } from '@/services/vehicleService';
import type { Device } from '@/types/device';
import type { PaginatedResponse, Vehicle } from '@/types/vehicle';

const devicesResponse = ref<PaginatedResponse<Device> | null>(null);
const vehicles = ref<Vehicle[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creatingDevice = ref(false);
const editingDevice = ref<Device | null>(null);
const deletingDeviceId = ref<number | null>(null);

const devices = computed(() => devicesResponse.value?.data ?? []);

const vehicleNames = computed(() => {
    return new Map(
        vehicles.value.map((vehicle) => [
            vehicle.id,
            `${vehicle.manufacturer} ${vehicle.model} (${vehicle.registration_number})`,
        ]),
    );
});

async function loadDevices(page = 1): Promise<void> {
    if (page < 1) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        devicesResponse.value = await getDevices(page);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load devices.';
    } finally {
        loading.value = false;
    }
}

async function loadVehicles(): Promise<void> {
    try {
        const response = await getVehicles();

        vehicles.value = response.data;
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load vehicles.';
    }
}

function startCreatingDevice(): void {
    if (vehicles.value.length === 0) {
        return;
    }

    editingDevice.value = null;
    creatingDevice.value = true;
}

function startEditingDevice(device: Device): void {
    creatingDevice.value = false;
    editingDevice.value = device;
}

function cancelDeviceForm(): void {
    creatingDevice.value = false;
    editingDevice.value = null;
}

async function handleDeviceSaved(): Promise<void> {
    cancelDeviceForm();

    await loadDevices(1);
}

async function handleDeleteDevice(device: Device): Promise<void> {
    const confirmed = window.confirm(
        `Delete "${device.name}" (${device.unique_id})? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    deletingDeviceId.value = device.id;
    error.value = null;

    try {
        await deleteDevice(device);

        if (editingDevice.value?.id === device.id) {
            cancelDeviceForm();
        }

        await loadDevices(devicesResponse.value?.meta.current_page ?? 1);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to delete device.';
    } finally {
        deletingDeviceId.value = null;
    }
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

function statusVariant(
    status: Device['status'],
): 'success' | 'neutral' | 'danger' {
    if (status === 'active') {
        return 'success';
    }

    if (status === 'offline') {
        return 'danger';
    }

    return 'neutral';
}

function statusLabel(status: Device['status']): string {
    return status.charAt(0).toUpperCase() + status.slice(1);
}

onMounted(async () => {
    await Promise.all([loadDevices(), loadVehicles()]);
});
</script>

<template>
    <Head title="Devices" />

    <AppLayout
        title="Devices"
        description="Manage GPS tracking devices"
        active-navigation="Devices"
    >
        <div class="mb-7 flex flex-col gap-4">
            <PageHeader
                eyebrow="Device management"
                title="Devices"
                description="Manage GPS devices assigned to your vehicles."
                show-refresh
                @refresh="loadDevices()"
            />

            <div class="flex justify-end">
                <AppButton
                    v-if="!creatingDevice && !editingDevice"
                    :disabled="vehicles.length === 0"
                    @click="startCreatingDevice"
                >
                    Create device
                </AppButton>
            </div>
        </div>

        <AppCard v-if="creatingDevice || editingDevice" class="mb-6">
            <div class="mb-5">
                <h3 class="text-lg font-semibold text-content">
                    {{ editingDevice ? 'Edit device' : 'Create device' }}
                </h3>

                <p class="mt-1 text-sm text-muted">
                    {{
                        editingDevice
                            ? 'Update the device assignment and settings.'
                            : 'Assign a GPS tracking device to a vehicle.'
                    }}
                </p>
            </div>

            <DeviceForm
                :key="editingDevice?.id ?? 'create'"
                :device="editingDevice ?? undefined"
                :vehicles="vehicles"
                @saved="handleDeviceSaved"
                @cancel="cancelDeviceForm"
            />
        </AppCard>

        <AppCard :padding="false">
            <LoadingState v-if="loading" message="Loading devices..." />

            <ErrorState
                v-else-if="error"
                title="Unable to load devices"
                :description="error"
                retryable
                @retry="loadDevices()"
            />

            <EmptyState
                v-else-if="devices.length === 0"
                title="No devices yet"
                description="Create your first device to start tracking vehicles."
                icon="devices"
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
                                Device
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Vehicle
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Traccar
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Last sync
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
                            v-for="device in devices"
                            :key="device.id"
                            class="transition hover:bg-surface-muted"
                        >
                            <td class="px-5 py-4">
                                <div class="min-w-40">
                                    <p class="font-medium text-content">
                                        {{ device.name }}
                                    </p>

                                    <p class="mt-1 text-xs text-muted">
                                        {{ device.unique_id }}
                                    </p>
                                </div>
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ vehicleNames.get(device.vehicle_id) ?? '—' }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <span
                                    v-if="device.traccar_device_id !== null"
                                    class="text-content-secondary"
                                >
                                    #{{ device.traccar_device_id }}
                                </span>

                                <StatusBadge v-else variant="neutral">
                                    Not synchronized
                                </StatusBadge>
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDateTime(device.last_sync_at) }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <StatusBadge
                                    :variant="statusVariant(device.status)"
                                    dot
                                >
                                    {{ statusLabel(device.status) }}
                                </StatusBadge>
                            </td>

                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <AppButton
                                    variant="secondary"
                                    size="sm"
                                    :disabled="deletingDeviceId !== null"
                                    @click="startEditingDevice(device)"
                                >
                                    Edit
                                </AppButton>

                                <AppButton
                                    variant="danger"
                                    size="sm"
                                    class="ml-2"
                                    :loading="deletingDeviceId === device.id"
                                    :disabled="deletingDeviceId !== null"
                                    @click="handleDeleteDevice(device)"
                                >
                                    Delete
                                </AppButton>
                            </td>
                        </tr>
                    </tbody>
                </AppTable>

                <AppPagination
                    v-if="devicesResponse"
                    :current-page="devicesResponse.meta.current_page"
                    :last-page="devicesResponse.meta.last_page"
                    :from="devicesResponse.meta.from"
                    :to="devicesResponse.meta.to"
                    :total="devicesResponse.meta.total"
                    item-label="devices"
                    @change="loadDevices"
                />
            </template>
        </AppCard>
    </AppLayout>
</template>
