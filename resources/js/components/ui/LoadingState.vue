<script setup lang="ts">
withDefaults(
    defineProps<{
        message?: string;
    }>(),
    {
        message: 'Loading...',
    },
);
</script>

<template>
    <div
        class="flex min-h-40 flex-col items-center justify-center px-6 py-10 text-center"
        role="status"
        aria-live="polite"
    >
        <svg
            class="h-6 w-6 animate-spin text-brand"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="9"
                stroke="currentColor"
                stroke-width="3"
            />

            <path
                class="opacity-75"
                fill="currentColor"
                d="M21 12a9 9 0 0 0-9-9v3a6 6 0 0 1 6 6h3Z"
            />
        </svg>

        <p class="mt-3 text-sm font-medium text-muted">
            {{ message }}
        </p>
    </div>
</template>