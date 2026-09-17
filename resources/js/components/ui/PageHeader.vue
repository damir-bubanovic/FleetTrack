<script setup lang="ts">
import AppButton from '@/components/ui/AppButton.vue';

withDefaults(
    defineProps<{
        eyebrow?: string;
        title: string;
        description?: string;
        showLiveStatus?: boolean;
        showRefresh?: boolean;
    }>(),
    {
        eyebrow: undefined,
        description: undefined,
        showLiveStatus: false,
        showRefresh: false,
    },
);

const emit = defineEmits<{
    refresh: [];
}>();
</script>

<template>
    <div
        class="mb-7 flex flex-col justify-between gap-4 sm:flex-row sm:items-end"
    >
        <div>
            <p
                v-if="eyebrow"
                class="text-xs font-semibold uppercase tracking-[0.12em] text-brand-dark"
            >
                {{ eyebrow }}
            </p>

            <h2
                class="mt-1 text-2xl font-semibold tracking-tight text-content"
            >
                {{ title }}
            </h2>

            <p
                v-if="description"
                class="mt-1 text-sm text-muted"
            >
                {{ description }}
            </p>
        </div>

        <div
            v-if="showLiveStatus || showRefresh"
            class="flex items-center gap-2"
        >
            <span
                v-if="showLiveStatus"
                class="inline-flex items-center gap-2 rounded-lg border border-border-default bg-surface px-3 py-2 text-xs font-medium text-muted"
            >
                <span
                    class="h-2 w-2 rounded-full bg-success"
                    aria-hidden="true"
                />

                Live data
            </span>

            <AppButton
                v-if="showRefresh"
                icon="refresh"
                @click="emit('refresh')"
            >
                Refresh
            </AppButton>
        </div>
    </div>
</template>