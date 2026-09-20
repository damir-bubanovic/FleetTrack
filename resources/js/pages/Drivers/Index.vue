<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import DriverForm from '@/components/drivers/DriverForm.vue';
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
import { deleteDriver, getDrivers } from '@/services/driverService';
import { getFleets } from '@/services/fleetService';
import type { Driver } from '@/types/driver';
import type { Fleet } from '@/types/fleet';
import type { PaginatedResponse } from '@/types/vehicle';

const driversResponse = ref<PaginatedResponse<Driver> | null>(null);
const fleets = ref<Fleet[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const creatingDriver = ref(false);
const editingDriver = ref<Driver | null>(null);
const deletingDriverId = ref<number | null>(null);

const drivers = computed(() => driversResponse.value?.data ?? []);

const fleetNames = computed(() => {
    return new Map(fleets.value.map((fleet) => [fleet.id, fleet.name]));
});

async function loadDrivers(page = 1): Promise<void> {
    if (page < 1) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        driversResponse.value = await getDrivers(page);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load drivers.';
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

function startCreatingDriver(): void {
    if (fleets.value.length === 0) {
        return;
    }

    editingDriver.value = null;
    creatingDriver.value = true;
}

function startEditingDriver(driver: Driver): void {
    creatingDriver.value = false;
    editingDriver.value = driver;
}

function cancelDriverForm(): void {
    creatingDriver.value = false;
    editingDriver.value = null;
}

async function handleDriverSaved(): Promise<void> {
    cancelDriverForm();

    await loadDrivers(1);
}

async function handleDeleteDriver(driver: Driver): Promise<void> {
    const confirmed = window.confirm(
        `Delete "${driver.full_name}" (${driver.employee_number})? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    deletingDriverId.value = driver.id;
    error.value = null;

    try {
        await deleteDriver(driver);

        if (editingDriver.value?.id === driver.id) {
            cancelDriverForm();
        }

        await loadDrivers(driversResponse.value?.meta.current_page ?? 1);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to delete driver.';
    } finally {
        deletingDriverId.value = null;
    }
}

function formatDate(value: string): string {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value.slice(0, 10);
    }

    return new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    }).format(date);
}

function licenseExpired(driver: Driver): boolean {
    const expiry = new Date(driver.license_expiry_date);

    if (Number.isNaN(expiry.getTime())) {
        return false;
    }

    return expiry.getTime() < Date.now();
}

onMounted(async () => {
    await Promise.all([loadDrivers(), loadFleets()]);
});
</script>

<template>
    <Head title="Drivers" />

    <AppLayout
        title="Drivers"
        description="Manage fleet drivers"
        active-navigation="Drivers"
    >
        <div class="mb-7 flex flex-col gap-4">
            <PageHeader
                eyebrow="Driver management"
                title="Drivers"
                description="Manage drivers across your fleets."
                show-refresh
                @refresh="loadDrivers()"
            />

            <div class="flex justify-end">
                <AppButton
                    v-if="!creatingDriver && !editingDriver"
                    :disabled="fleets.length === 0"
                    @click="startCreatingDriver"
                >
                    Create driver
                </AppButton>
            </div>
        </div>

        <AppCard v-if="creatingDriver || editingDriver" class="mb-6">
            <div class="mb-5">
                <h3 class="text-lg font-semibold text-content">
                    {{ editingDriver ? 'Edit driver' : 'Create driver' }}
                </h3>

                <p class="mt-1 text-sm text-muted">
                    {{
                        editingDriver
                            ? 'Update the driver information and settings.'
                            : 'Add a new driver to one of your fleets.'
                    }}
                </p>
            </div>

            <DriverForm
                :key="editingDriver?.id ?? 'create'"
                :driver="editingDriver ?? undefined"
                :fleets="fleets"
                @saved="handleDriverSaved"
                @cancel="cancelDriverForm"
            />
        </AppCard>

        <AppCard :padding="false">
            <LoadingState v-if="loading" message="Loading drivers..." />

            <ErrorState
                v-else-if="error"
                title="Unable to load drivers"
                :description="error"
                retryable
                @retry="loadDrivers()"
            />

            <EmptyState
                v-else-if="drivers.length === 0"
                title="No drivers yet"
                description="Create your first driver to start managing your fleet team."
                icon="drivers"
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
                                Driver
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Employee
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Fleet
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                License
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
                            v-for="driver in drivers"
                            :key="driver.id"
                            class="transition hover:bg-surface-muted"
                        >
                            <td class="px-5 py-4">
                                <div class="min-w-44">
                                    <p class="font-medium text-content">
                                        {{ driver.full_name }}
                                    </p>

                                    <p
                                        v-if="driver.email"
                                        class="mt-1 text-xs text-muted"
                                    >
                                        {{ driver.email }}
                                    </p>

                                    <p
                                        v-else-if="driver.phone"
                                        class="mt-1 text-xs text-muted"
                                    >
                                        {{ driver.phone }}
                                    </p>
                                </div>
                            </td>

                            <td
                                class="px-5 py-4 font-medium whitespace-nowrap text-content-secondary"
                            >
                                {{ driver.employee_number }}
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ fleetNames.get(driver.fleet_id) ?? '—' }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="min-w-40">
                                    <p class="text-content-secondary">
                                        {{ driver.license_number }}
                                    </p>

                                    <p class="mt-1 text-xs text-muted">
                                        Category
                                        {{ driver.license_category }}
                                        ·
                                        {{
                                            formatDate(
                                                driver.license_expiry_date,
                                            )
                                        }}
                                    </p>

                                    <StatusBadge
                                        v-if="licenseExpired(driver)"
                                        variant="danger"
                                        class="mt-2"
                                    >
                                        License expired
                                    </StatusBadge>
                                </div>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <StatusBadge
                                    :variant="
                                        driver.is_active ? 'success' : 'neutral'
                                    "
                                    dot
                                >
                                    {{
                                        driver.is_active ? 'Active' : 'Inactive'
                                    }}
                                </StatusBadge>
                            </td>

                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <AppButton
                                    variant="secondary"
                                    size="sm"
                                    :disabled="deletingDriverId !== null"
                                    @click="startEditingDriver(driver)"
                                >
                                    Edit
                                </AppButton>

                                <AppButton
                                    variant="danger"
                                    size="sm"
                                    class="ml-2"
                                    :loading="deletingDriverId === driver.id"
                                    :disabled="deletingDriverId !== null"
                                    @click="handleDeleteDriver(driver)"
                                >
                                    Delete
                                </AppButton>
                            </td>
                        </tr>
                    </tbody>
                </AppTable>

                <AppPagination
                    v-if="driversResponse"
                    :current-page="driversResponse.meta.current_page"
                    :last-page="driversResponse.meta.last_page"
                    :from="driversResponse.meta.from"
                    :to="driversResponse.meta.to"
                    :total="driversResponse.meta.total"
                    item-label="drivers"
                    @change="loadDrivers"
                />
            </template>
        </AppCard>
    </AppLayout>
</template>
