<script setup lang="ts">
import AppCard from '@/components/ui/AppCard.vue';
import StatusBadge from '@/components/ui/StatusBadge.vue';

type AlertSeverity = 'Critical' | 'Warning' | 'Info';

type RecentAlert = {
    title: string;
    vehicle: string;
    time: string;
    severity: AlertSeverity;
};

defineProps<{
    alerts: readonly RecentAlert[];
}>();

function severityVariant(
    severity: AlertSeverity,
): 'danger' | 'warning' | 'neutral' {
    if (severity === 'Critical') {
        return 'danger';
    }

    if (severity === 'Warning') {
        return 'warning';
    }

    return 'neutral';
}
</script>

<template>
    <AppCard>
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-content">
                    Recent alerts
                </h3>

                <p class="mt-1 text-xs text-muted">
                    Latest fleet activity requiring attention
                </p>
            </div>

            <button
                type="button"
                class="text-xs font-semibold text-brand-dark transition hover:text-brand-hover"
            >
                View all
            </button>
        </div>

        <div class="mt-4 divide-y divide-border-default">
            <div
                v-for="alert in alerts"
                :key="`${alert.vehicle}-${alert.time}`"
                class="py-4 first:pt-0 last:pb-0"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-content">
                            {{ alert.title }}
                        </p>

                        <div
                            class="mt-1 flex flex-wrap items-center gap-x-2 text-xs text-muted"
                        >
                            <span>{{ alert.vehicle }}</span>

                            <span aria-hidden="true">·</span>

                            <span>{{ alert.time }}</span>
                        </div>
                    </div>

                    <StatusBadge :variant="severityVariant(alert.severity)" dot>
                        {{ alert.severity }}
                    </StatusBadge>
                </div>
            </div>
        </div>
    </AppCard>
</template>
