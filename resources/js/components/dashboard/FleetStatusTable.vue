<script setup lang="ts">
import AppCard from '@/components/ui/AppCard.vue';
import AppTable from '@/components/ui/AppTable.vue';

type FleetStatus = {
    name: string;
    vehicles: number;
    online: number;
    offline: number;
};

defineProps<{
    fleets: FleetStatus[];
}>();
</script>

<template>
    <AppCard :padding="false">
        <div
            class="flex items-center justify-between border-b border-border-default px-5 py-4"
        >
            <div>
                <h3 class="text-sm font-semibold text-content">Fleet status</h3>

                <p class="mt-1 text-xs text-muted">
                    Vehicle connectivity by fleet
                </p>
            </div>

            <button
                type="button"
                class="text-xs font-semibold text-brand-dark transition hover:text-brand-hover"
            >
                View fleets
            </button>
        </div>

        <AppTable>
            <thead>
                <tr class="border-b border-border-default bg-surface-muted">
                    <th
                        class="px-5 py-3 text-left text-xs font-semibold text-muted"
                    >
                        Fleet
                    </th>

                    <th
                        class="px-5 py-3 text-right text-xs font-semibold text-muted"
                    >
                        Vehicles
                    </th>

                    <th
                        class="px-5 py-3 text-right text-xs font-semibold text-muted"
                    >
                        Online
                    </th>

                    <th
                        class="px-5 py-3 text-right text-xs font-semibold text-muted"
                    >
                        Offline
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-border-default">
                <tr
                    v-for="fleet in fleets"
                    :key="fleet.name"
                    class="transition hover:bg-surface-muted"
                >
                    <td
                        class="px-5 py-4 text-sm font-medium whitespace-nowrap text-content"
                    >
                        {{ fleet.name }}
                    </td>

                    <td
                        class="px-5 py-4 text-right text-sm whitespace-nowrap text-content-secondary"
                    >
                        {{ fleet.vehicles }}
                    </td>

                    <td
                        class="px-5 py-4 text-right text-sm font-medium whitespace-nowrap text-success-dark"
                    >
                        {{ fleet.online }}
                    </td>

                    <td
                        class="px-5 py-4 text-right text-sm font-medium whitespace-nowrap text-warning-dark"
                    >
                        {{ fleet.offline }}
                    </td>
                </tr>
            </tbody>
        </AppTable>
    </AppCard>
</template>
