<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

import AppCard from '@/components/ui/AppCard.vue';
import AppPagination from '@/components/ui/AppPagination.vue';
import AppTable from '@/components/ui/AppTable.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import ErrorState from '@/components/ui/ErrorState.vue';
import LoadingState from '@/components/ui/LoadingState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { getFleets } from '@/services/fleetService';
import type { Fleet, PaginatedResponse } from '@/types/fleet';

const fleetsResponse = ref<PaginatedResponse<Fleet> | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const fleets = computed(() => fleetsResponse.value?.data ?? []);

async function loadFleets(page = 1): Promise<void> {
    if (page < 1) {
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        fleetsResponse.value = await getFleets(page);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load fleets.';
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    void loadFleets();
});
</script>

<template>
    <Head title="Fleets" />

    <AppLayout
        title="Fleets"
        description="Manage vehicle fleets"
        active-navigation="Fleets"
    >
        <PageHeader
            eyebrow="Fleet management"
            title="Fleets"
            description="Manage and organize your vehicle fleets."
            show-refresh
            @refresh="loadFleets()"
        />

        <AppCard :padding="false">
            <LoadingState v-if="loading" message="Loading fleets..." />

            <ErrorState
                v-else-if="error"
                title="Unable to load fleets"
                :description="error"
                retryable
                @retry="loadFleets()"
            />

            <EmptyState
                v-else-if="fleets.length === 0"
                title="No fleets yet"
                description="Create your first fleet to start organizing vehicles."
                icon="fleets"
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
                                Fleet
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Code
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Contact
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Timezone
                            </th>

                            <th
                                class="px-5 py-3 text-xs font-semibold tracking-wide text-muted uppercase"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border-default">
                        <tr
                            v-for="fleet in fleets"
                            :key="fleet.id"
                            class="transition hover:bg-surface-muted"
                        >
                            <td class="px-5 py-4">
                                <div class="min-w-48">
                                    <p class="font-medium text-content">
                                        {{ fleet.name }}
                                    </p>

                                    <p
                                        v-if="fleet.address"
                                        class="mt-1 max-w-xs truncate text-xs text-muted"
                                    >
                                        {{ fleet.address }}
                                    </p>
                                </div>
                            </td>

                            <td
                                class="px-5 py-4 font-medium whitespace-nowrap text-content-secondary"
                            >
                                {{ fleet.code }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="min-w-44 text-sm">
                                    <p
                                        v-if="fleet.email"
                                        class="text-content-secondary"
                                    >
                                        {{ fleet.email }}
                                    </p>

                                    <p
                                        v-if="fleet.phone"
                                        class="mt-1 text-xs text-muted"
                                    >
                                        {{ fleet.phone }}
                                    </p>

                                    <span
                                        v-if="!fleet.email && !fleet.phone"
                                        class="text-muted"
                                    >
                                        —
                                    </span>
                                </div>
                            </td>

                            <td
                                class="px-5 py-4 whitespace-nowrap text-content-secondary"
                            >
                                {{ fleet.timezone ?? '—' }}
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap">
                                <StatusBadge
                                    :variant="
                                        fleet.is_active ? 'success' : 'neutral'
                                    "
                                    dot
                                >
                                    {{
                                        fleet.is_active ? 'Active' : 'Inactive'
                                    }}
                                </StatusBadge>
                            </td>
                        </tr>
                    </tbody>
                </AppTable>

                <AppPagination
                    v-if="fleetsResponse"
                    :current-page="fleetsResponse.meta.current_page"
                    :last-page="fleetsResponse.meta.last_page"
                    :from="fleetsResponse.meta.from"
                    :to="fleetsResponse.meta.to"
                    :total="fleetsResponse.meta.total"
                    item-label="fleets"
                    @change="loadFleets"
                />
            </template>
        </AppCard>
    </AppLayout>
</template>
