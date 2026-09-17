<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, useAttrs } from 'vue';

type StatusVariant =
    | 'success'
    | 'warning'
    | 'danger'
    | 'info'
    | 'neutral';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        variant?: StatusVariant;
        dot?: boolean;
    }>(),
    {
        variant: 'neutral',
        dot: false,
    },
);

const attrs = useAttrs();

const variantClasses: Record<StatusVariant, string> = {
    success:
        'bg-success-soft text-success-dark ring-success/10',
    warning:
        'bg-warning-soft text-warning-dark ring-warning/10',
    danger:
        'bg-danger-soft text-danger-dark ring-danger/10',
    info:
        'bg-info-soft text-info-dark ring-info/10',
    neutral:
        'bg-surface-muted text-content-secondary ring-border-strong',
};

const dotClasses: Record<StatusVariant, string> = {
    success: 'bg-success',
    warning: 'bg-warning',
    danger: 'bg-danger',
    info: 'bg-info',
    neutral: 'bg-subtle',
};

const classes = computed(() =>
    cn(
        'inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-semibold ring-1 ring-inset',
        variantClasses[props.variant],
        attrs.class,
    ),
);
</script>

<template>
    <span
        v-bind="attrs"
        :class="classes"
    >
        <span
            v-if="dot"
            class="h-1.5 w-1.5 shrink-0 rounded-full"
            :class="dotClasses[variant]"
            aria-hidden="true"
        />

        <slot />
    </span>
</template>