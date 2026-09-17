<script setup lang="ts">
import AppButton from '@/components/ui/AppButton.vue';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        retryable?: boolean;
    }>(),
    {
        title: 'Something went wrong',
        description: 'Unable to load the requested data.',
        retryable: false,
    },
);

const emit = defineEmits<{
    retry: [];
}>();
</script>

<template>
    <div
        class="flex min-h-48 flex-col items-center justify-center px-6 py-10 text-center"
        role="alert"
    >
        <div
            class="flex h-11 w-11 items-center justify-center rounded-xl bg-danger-soft text-danger"
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"
                class="h-5 w-5"
                aria-hidden="true"
            >
                <circle cx="12" cy="12" r="9" />
                <path d="M12 8v5" />
                <path d="M12 16h.01" />
            </svg>
        </div>

        <h3 class="mt-3 text-sm font-semibold text-content">
            {{ title }}
        </h3>

        <p class="mt-1 max-w-md text-sm text-muted">
            {{ description }}
        </p>

        <AppButton
            v-if="retryable"
            variant="secondary"
            class="mt-4"
            @click="emit('retry')"
        >
            Try again
        </AppButton>

        <div
            v-if="$slots.action"
            class="mt-4"
        >
            <slot name="action" />
        </div>
    </div>
</template>