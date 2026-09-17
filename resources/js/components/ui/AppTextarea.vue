<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

const props = withDefaults(
    defineProps<{
        modelValue?: string | null;
        invalid?: boolean;
        disabled?: boolean;
    }>(),
    {
        modelValue: '',
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
        'block min-h-24 w-full resize-y rounded-lg border bg-surface px-3 py-2 text-sm text-content shadow-sm transition',
        'placeholder:text-subtle',
        'focus:outline-none focus:ring-2',
        'disabled:cursor-not-allowed disabled:bg-surface-muted disabled:text-muted disabled:opacity-70',
        props.invalid
            ? 'border-danger focus:border-danger focus:ring-danger/20'
            : 'border-border-default hover:border-border-strong focus:border-brand focus:ring-brand/20',
        attrs.class,
    ),
);

function handleInput(event: Event): void {
    emit(
        'update:modelValue',
        (event.target as HTMLTextAreaElement).value,
    );
}
</script>

<template>
    <textarea
        v-bind="attrs"
        :value="modelValue ?? ''"
        :disabled="disabled"
        :aria-invalid="invalid || undefined"
        :class="classes"
        @input="handleInput"
    />
</template>