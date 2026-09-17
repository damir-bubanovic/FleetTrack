<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        modelValue?: boolean;
        disabled?: boolean;
        invalid?: boolean;
    }>(),
    {
        modelValue: false,
        disabled: false,
        invalid: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const attrs = useAttrs();

const classes = computed(() =>
    cn(
        'h-4 w-4 rounded border bg-surface text-brand transition',
        'focus:ring-2 focus:ring-brand/20 focus:ring-offset-0',
        'disabled:cursor-not-allowed disabled:opacity-50',
        props.invalid ? 'border-danger' : 'border-border-strong',
        attrs.class,
    ),
);

function handleChange(event: Event): void {
    emit(
        'update:modelValue',
        (event.target as HTMLInputElement).checked,
    );
}
</script>

<template>
    <input
        v-bind="attrs"
        type="checkbox"
        :checked="modelValue"
        :disabled="disabled"
        :aria-invalid="invalid || undefined"
        :class="classes"
        @change="handleChange"
    />
</template>