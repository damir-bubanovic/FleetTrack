<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        padding?: boolean;
    }>(),
    {
        padding: true,
    },
);

const attrs = useAttrs();

const classes = computed(() =>
    cn(
        'rounded-xl border border-border-default bg-surface',
        props.padding && 'p-5',
        attrs.class,
    ),
);
</script>

<template>
    <section
        v-bind="attrs"
        :class="classes"
    >
        <slot />
    </section>
</template>