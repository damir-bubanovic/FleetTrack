<script setup lang="ts">
import AppCard from '@/components/ui/AppCard.vue';
import AppIcon from '@/components/ui/AppIcon.vue';

type MetricType = 'vehicles' | 'online' | 'offline' | 'alerts';

const props = defineProps<{
    label: string;
    value: number;
    detail: string;
    icon: MetricType;
}>();

function iconClass(): string {
    if (props.icon === 'online') {
        return 'bg-success-soft text-success';
    }

    if (props.icon === 'offline') {
        return 'bg-warning-soft text-warning';
    }

    if (props.icon === 'alerts') {
        return 'bg-danger-soft text-danger';
    }

    return 'bg-brand-soft text-brand-dark';
}
</script>

<template>
    <AppCard>
        <div class="flex items-start justify-between">
            <div>
                <p class="text-sm font-medium text-muted">
                    {{ label }}
                </p>

                <p
                    class="mt-2 text-3xl font-semibold tracking-tight text-content"
                >
                    {{ value }}
                </p>
            </div>

            <div
                class="flex h-10 w-10 items-center justify-center rounded-lg"
                :class="iconClass()"
            >
                <AppIcon :name="icon" class="h-5 w-5" />
            </div>
        </div>

        <p class="mt-3 text-xs text-muted">
            {{ detail }}
        </p>
    </AppCard>
</template>
