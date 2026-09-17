<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        compact?: boolean;
    }>(),
    {
        compact: false,
    },
);

const attrs = useAttrs();

const classes = computed(() =>
    cn(
        'w-full text-left',
        props.compact ? 'text-xs' : 'text-sm',
        attrs.class,
    ),
);
</script>

<template>
    <div class="overflow-x-auto">
        <table
            v-bind="attrs"
            :class="classes"
        >
            <slot />
        </table>
    </div>
</template>