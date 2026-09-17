<script setup lang="ts">
import AppButton from '@/components/ui/AppButton.vue';

defineProps<{
    currentPage: number;
    lastPage: number;
    from: number | null;
    to: number | null;
    total: number;
    itemLabel?: string;
}>();

const emit = defineEmits<{
    change: [page: number];
}>();
</script>

<template>
    <div
        class="flex flex-col gap-3 border-t border-border-default px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-xs text-muted">
            Showing {{ from ?? 0 }}–{{ to ?? 0 }} of {{ total }}
            {{ itemLabel ?? 'items' }}
        </p>

        <div class="flex items-center gap-3">
            <span class="text-xs text-muted">
                Page {{ currentPage }} of {{ lastPage }}
            </span>

            <div class="flex items-center gap-2">
                <AppButton
                    variant="secondary"
                    size="sm"
                    :disabled="currentPage <= 1"
                    @click="emit('change', currentPage - 1)"
                >
                    Previous
                </AppButton>

                <AppButton
                    variant="secondary"
                    size="sm"
                    :disabled="currentPage >= lastPage"
                    @click="emit('change', currentPage + 1)"
                >
                    Next
                </AppButton>
            </div>
        </div>
    </div>
</template>