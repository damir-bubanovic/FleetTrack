<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import GeofenceForm from '@/components/geofences/GeofenceForm.vue';
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
import { deleteGeofence, getGeofences } from '@/services/geofenceService';
import type { Geofence } from '@/types/geofence';
import type { PaginatedResponse } from '@/types/vehicle';

const geofencesResponse = ref<PaginatedResponse<Geofence> | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
const creatingGeofence = ref(false);
const editingGeofence = ref<Geofence | null>(null);
const deletingGeofenceId = ref<number | null>(null);

const geofences = computed(() => geofencesResponse.value?.data ?? []);

async function loadGeofences(page = 1): Promise<void> {
    if (page < 1) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        geofencesResponse.value = await getGeofences(page);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load geofences.';
    } finally {
        loading.value = false;
    }
}

function startCreatingGeofence(): void {
    editingGeofence.value = null;
    creatingGeofence.value = true;
}

function startEditingGeofence(geofence: Geofence): void {
    creatingGeofence.value = false;
    editingGeofence.value = geofence;
}

function cancelGeofenceForm(): void {
    creatingGeofence.value = false;
    editingGeofence.value = null;
}

async function handleGeofenceSaved(): Promise<void> {
    cancelGeofenceForm();

    await loadGeofences(1);
}

async function handleDeleteGeofence(geofence: Geofence): Promise<void> {
    const confirmed = window.confirm(
        `Delete "${geofence.name}"? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    deletingGeofenceId.value = geofence.id;
    error.value = null;

    try {
        await deleteGeofence(geofence);

        if (editingGeofence.value?.id === geofence.id) {
            cancelGeofenceForm();
        }

        await loadGeofences(geofencesResponse.value?.meta.current_page ?? 1);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to delete geofence.';
    } finally {
        deletingGeofenceId.value = null;
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

onMounted(async () => {
    await loadGeofences();
});
</script>

<template>
    <Head title="Geofences" />

    <AppLayout
        title="Geofences"
        description="Manage fleet geofences"
        active-navigation="Geofences"
    >
        <div class="mb-7 flex flex-col gap-4">
            <PageHeader
                eyebrow="Geofence management"
                title="Geofences"
                description="Create and manage geographic boundaries for your fleet."
                show-refresh
                @refresh="loadGeofences()"
            />

            <div class="flex justify-end">
                <AppButton
                    v-if="!creatingGeofence && !editingGeofence"
                    @click="startCreatingGeofence"
                >
                    Create geofence
                </AppButton>
            </div>
        </div>

        <AppCard v-if="creatingGeofence || editingGeofence" class="mb-6">
            <div class="mb-5">
                <h3 class="text-lg font-semibold text-content">
                    {{ editingGeofence ? 'Edit geofence' : 'Create geofence' }}
                </h3>

                <p class="mt-1 text-sm text-muted">
                    {{
                        editingGeofence
                            ? 'Update the geofence boundary and settings.'
                            : 'Create a geographic boundary for your fleet.'
                    }}
                </p>
            </div>

            <GeofenceForm
                :key="editingGeofence?.id ?? 'create'"
                :geofence="editingGeofence ?? undefined"
                @saved="handleGeofenceSaved"
                @cancel="cancelGeofenceForm"
            />
        </AppCard>

        <AppCard :padding="false">
            <LoadingState v-if="loading" message="Loading geofences..." />

            <ErrorState
                v-else-if="error"
                title="Unable to load geofences"
                :description="error"
                retryable
                @retry="loadGeofences()"
            />

            <EmptyState
                v-else-if="geofences.length === 0"
                title="No geofences yet"
                description="Create your first geofence to define a geographic boundary for your fleet."
                icon="geofences"
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
                                Geofence
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Area
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
                            v-for="geofence in geofences"
                            :key="geofence.id"
                            class="transition hover:bg-surface-muted"
                        >
                            <td class="px-5 py-4">
                                <div class="min-w-40">
                                    <p class="font-medium text-content">
                                        {{ geofence.name }}
                                    </p>

                                    <p
                                        v-if="geofence.description"
                                        class="mt-1 max-w-xs truncate text-xs text-muted"
                                    >
                                        {{ geofence.description }}
                                    </p>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <p
                                    class="max-w-xs truncate font-mono text-xs text-content-secondary"
                                    :title="geofence.area"
                                >
                                    {{ geofence.area }}
                                </p>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <span
                                    v-if="geofence.traccar_geofence_id !== null"
                                    class="text-content-secondary"
                                >
                                    #{{ geofence.traccar_geofence_id }}
                                </span>

                                <StatusBadge v-else variant="neutral">
                                    Not synchronized
                                </StatusBadge>
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ formatDateTime(geofence.last_sync_at) }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <StatusBadge
                                    :variant="
                                        geofence.is_active
                                            ? 'success'
                                            : 'neutral'
                                    "
                                    dot
                                >
                                    {{
                                        geofence.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </StatusBadge>
                            </td>

                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <AppButton
                                    variant="secondary"
                                    size="sm"
                                    :disabled="deletingGeofenceId !== null"
                                    @click="startEditingGeofence(geofence)"
                                >
                                    Edit
                                </AppButton>

                                <AppButton
                                    variant="danger"
                                    size="sm"
                                    class="ml-2"
                                    :loading="
                                        deletingGeofenceId === geofence.id
                                    "
                                    :disabled="deletingGeofenceId !== null"
                                    @click="handleDeleteGeofence(geofence)"
                                >
                                    Delete
                                </AppButton>
                            </td>
                        </tr>
                    </tbody>
                </AppTable>

                <AppPagination
                    v-if="geofencesResponse"
                    :current-page="geofencesResponse.meta.current_page"
                    :last-page="geofencesResponse.meta.last_page"
                    :from="geofencesResponse.meta.from"
                    :to="geofencesResponse.meta.to"
                    :total="geofencesResponse.meta.total"
                    item-label="geofences"
                    @change="loadGeofences"
                />
            </template>
        </AppCard>
    </AppLayout>
</template>
