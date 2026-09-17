<script setup lang="ts">
import AppIcon from '@/components/ui/AppIcon.vue';
import { cn } from '@/lib/utils';
import { computed, useAttrs } from 'vue';

type ButtonVariant =
    | 'primary'
    | 'secondary'
    | 'danger'
    | 'ghost';

type ButtonSize = 'sm' | 'md' | 'lg';

type IconName = InstanceType<typeof AppIcon>['$props']['name'];

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        variant?: ButtonVariant;
        size?: ButtonSize;
        type?: 'button' | 'submit' | 'reset';
        icon?: IconName;
        loading?: boolean;
        disabled?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'md',
        type: 'button',
        icon: undefined,
        loading: false,
        disabled: false,
    },
);

const attrs = useAttrs();

const variantClasses: Record<ButtonVariant, string> = {
    primary:
        'bg-brand text-sidebar hover:bg-brand-hover focus-visible:ring-brand',
    secondary:
        'border border-border-default bg-surface text-content-secondary hover:border-brand hover:bg-brand-soft hover:text-brand-dark focus-visible:ring-brand',
    danger:
        'bg-danger text-white hover:bg-danger-dark focus-visible:ring-danger',
    ghost:
        'bg-transparent text-muted hover:bg-surface-muted hover:text-content focus-visible:ring-border-strong',
};

const sizeClasses: Record<ButtonSize, string> = {
    sm: 'h-8 px-3 text-xs',
    md: 'h-9 px-4 text-sm',
    lg: 'h-11 px-5 text-sm',
};

const classes = computed(() =>
    cn(
        'inline-flex items-center justify-center rounded-lg font-semibold transition',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2',
        'disabled:pointer-events-none disabled:opacity-50',
        variantClasses[props.variant],
        sizeClasses[props.size],
        attrs.class,
    ),
);
</script>

<template>
    <button
        v-bind="attrs"
        :type="type"
        :disabled="disabled || loading"
        :class="classes"
    >
        <svg
            v-if="loading"
            class="mr-2 h-4 w-4 animate-spin"
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

        <AppIcon
            v-else-if="icon"
            :name="icon"
            class="mr-2 h-4 w-4"
        />

        <slot />
    </button>
</template>