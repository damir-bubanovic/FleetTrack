<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
        inputId?: string;
        error?: string | null;
        description?: string;
        required?: boolean;
    }>(),
    {
        label: undefined,
        inputId: undefined,
        error: undefined,
        description: undefined,
        required: false,
    },
);
</script>

<template>
    <div>
        <label
            v-if="label"
            :for="inputId"
            class="mb-1.5 block text-sm font-medium text-content-secondary"
        >
            {{ label }}

            <span
                v-if="required"
                class="ml-0.5 text-danger"
                aria-hidden="true"
            >
                *
            </span>
        </label>

        <slot />

        <p
            v-if="error"
            class="mt-1.5 text-xs font-medium text-danger-dark"
            role="alert"
        >
            {{ error }}
        </p>

        <p
            v-else-if="description"
            class="mt-1.5 text-xs text-muted"
        >
            {{ description }}
        </p>
    </div>
</template>