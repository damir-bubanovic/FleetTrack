<script setup lang="ts">
import AppButton from '@/components/ui/AppButton.vue';
import AppIcon from '@/components/ui/AppIcon.vue';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        companyName?: string;
        companyLocation?: string;
        hasNotifications?: boolean;
    }>(),
    {
        title: '',
        description: undefined,
        companyName: undefined,
        companyLocation: undefined,
        hasNotifications: false,
    },
);

const emit = defineEmits<{
    openNavigation: [];
}>();
</script>

<template>
    <header
        class="sticky top-0 z-30 flex h-20 items-center border-b border-border-default bg-surface/95 px-5 backdrop-blur lg:px-8"
    >
        <AppButton
            variant="ghost"
            size="sm"
            class="mr-4 h-9 w-9 px-0 lg:hidden"
            aria-label="Open navigation"
            aria-haspopup="dialog"
            @click="emit('openNavigation')"
        >
            <AppIcon name="menu" class="h-5 w-5" />
        </AppButton>

        <div v-if="title" class="min-w-0">
            <h1
                class="truncate text-lg font-semibold tracking-tight text-content"
            >
                {{ title }}
            </h1>

            <p
                v-if="description"
                class="mt-0.5 hidden truncate text-xs text-muted sm:block"
            >
                {{ description }}
            </p>
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-2">
            <AppButton
                variant="secondary"
                size="sm"
                class="relative h-9 w-9 px-0"
                aria-label="Notifications"
            >
                <AppIcon name="notification" class="h-[18px] w-[18px]" />

                <span
                    v-if="hasNotifications"
                    class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full border-2 border-surface bg-danger"
                    aria-hidden="true"
                />
            </AppButton>

            <div
                v-if="companyName"
                class="ml-2 hidden items-center border-l border-border-default pl-4 md:flex"
            >
                <div class="text-right">
                    <div
                        class="max-w-48 truncate text-sm font-medium text-content-secondary"
                    >
                        {{ companyName }}
                    </div>

                    <div
                        v-if="companyLocation"
                        class="max-w-48 truncate text-xs text-muted"
                    >
                        {{ companyLocation }}
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
