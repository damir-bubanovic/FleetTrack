<script setup lang="ts">
import AppIcon from '@/components/ui/AppIcon.vue';

type IconName = InstanceType<typeof AppIcon>['$props']['name'];

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        icon?: IconName;
    }>(),
    {
        description: undefined,
        icon: undefined,
    },
);
</script>

<template>
    <div
        class="flex min-h-48 flex-col items-center justify-center px-6 py-10 text-center"
    >
        <div
            v-if="icon"
            class="flex h-11 w-11 items-center justify-center rounded-xl bg-surface-muted text-muted"
        >
            <AppIcon :name="icon" class="h-5 w-5" />
        </div>

        <h3 class="mt-3 text-sm font-semibold text-content">
            {{ title }}
        </h3>

        <p
            v-if="description"
            class="mt-1 max-w-md text-sm text-muted"
        >
            {{ description }}
        </p>

        <div
            v-if="$slots.action"
            class="mt-4"
        >
            <slot name="action" />
        </div>
    </div>
</template>