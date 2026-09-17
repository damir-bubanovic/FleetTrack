<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, useAttrs } from 'vue';

type SelectValue = string | number | null;

type SelectOption = {
    label: string;
    value: string | number;
    disabled?: boolean;
};

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        modelValue?: SelectValue;
        options: readonly SelectOption[];
        placeholder?: string;
        invalid?: boolean;
        disabled?: boolean;
    }>(),
    {
        modelValue: null,
        placeholder: undefined,
        invalid: false,
        disabled: false,
    },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const attrs = useAttrs();

const classes = computed(() =>
    cn(
        'block h-10 w-full rounded-lg border bg-surface px-3 pr-9 text-sm text-content shadow-sm transition',
        'focus:outline-none focus:ring-2',
        'disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-muted disabled:opacity-70',
        props.invalid
            ? 'border-danger focus:border-danger focus:ring-danger/20'
            : 'border-border-default hover:border-border-strong focus:border-brand focus:ring-brand/20',
        attrs.class,
    ),
);

function handleChange(event: Event): void {
    emit(
        'update:modelValue',
        (event.target as HTMLSelectElement).value,
    );
}
</script>

<template>
    <select
        v-bind="attrs"
        :value="modelValue ?? ''"
        :disabled="disabled"
        :aria-invalid="invalid || undefined"
        :class="classes"
        @change="handleChange"
    >
        <option
            v-if="placeholder"
            value=""
            disabled
        >
            {{ placeholder }}
        </option>

        <option
            v-for="option in options"
            :key="option.value"
            :value="option.value"
            :disabled="option.disabled"
        >
            {{ option.label }}
        </option>
    </select>
</template>