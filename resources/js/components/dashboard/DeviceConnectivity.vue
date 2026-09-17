<script setup lang="ts">
import AppCard from '@/components/ui/AppCard.vue';

defineProps<{
    devices: number;
    online: number;
    offline: number;
}>();
</script>

<template>
    <AppCard class="mt-6">
        <div
            class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div>
                <h3 class="text-sm font-semibold text-content">
                    Device connectivity
                </h3>

                <p class="mt-1 text-xs text-muted">
                    Current synchronized tracking device status
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full bg-success"
                        aria-hidden="true"
                    />

                    <span class="text-muted">Online</span>

                    <span class="font-semibold text-content">
                        {{ online }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <span
                        class="h-2.5 w-2.5 rounded-full bg-warning"
                        aria-hidden="true"
                    />

                    <span class="text-muted">Offline</span>

                    <span class="font-semibold text-content">
                        {{ offline }}
                    </span>
                </div>

                <div class="border-l border-border-default pl-6 text-muted">
                    Total

                    <span class="ml-1 font-semibold text-content">
                        {{ devices }}
                    </span>
                </div>
            </div>
        </div>
    </AppCard>
</template>
