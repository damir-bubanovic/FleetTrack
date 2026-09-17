<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

import AppFooter from '@/components/app/AppFooter.vue';
import AppHeader from '@/components/app/AppHeader.vue';
import AppSidebar from '@/components/app/AppSidebar.vue';

withDefaults(
    defineProps<{
        title?: string;
        description?: string;
        activeNavigation?: string;
        companyName?: string;
        companyLocation?: string;
        userName?: string;
        userRole?: string;
        userInitials?: string;
        hasNotifications?: boolean;
    }>(),
    {
        title: undefined,
        description: undefined,
        activeNavigation: undefined,
        companyName: undefined,
        companyLocation: undefined,
        userName: undefined,
        userRole: undefined,
        userInitials: undefined,
        hasNotifications: false,
    },
);

const mobileNavigationOpen = ref(false);

function openMobileNavigation(): void {
    mobileNavigationOpen.value = true;
}

function closeMobileNavigation(): void {
    mobileNavigationOpen.value = false;
}

function handleKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape' && mobileNavigationOpen.value) {
        closeMobileNavigation();
    }
}

watch(mobileNavigationOpen, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
});

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <div class="min-h-screen bg-app font-sans text-content">
        <AppSidebar
            :active-item="activeNavigation"
            :user-name="userName"
            :user-role="userRole"
            :user-initials="userInitials"
        />

        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileNavigationOpen"
                class="fixed inset-0 z-40 bg-black/50 lg:hidden"
                aria-hidden="true"
                @click="closeMobileNavigation"
            />
        </Transition>

        <Transition
            enter-active-class="transition-transform duration-200 ease-out"
            enter-from-class="-translate-x-full"
            enter-to-class="translate-x-0"
            leave-active-class="transition-transform duration-200 ease-in"
            leave-from-class="translate-x-0"
            leave-to-class="-translate-x-full"
        >
            <div
                v-if="mobileNavigationOpen"
                class="fixed inset-y-0 left-0 z-50 lg:hidden"
                role="dialog"
                aria-modal="true"
                aria-label="Navigation"
            >
                <AppSidebar
                    mobile
                    :active-item="activeNavigation"
                    :user-name="userName"
                    :user-role="userRole"
                    :user-initials="userInitials"
                    @close="closeMobileNavigation"
                />
            </div>
        </Transition>

        <div class="flex min-h-screen flex-col lg:pl-64">
            <AppHeader
                :title="title"
                :description="description"
                :company-name="companyName"
                :company-location="companyLocation"
                :has-notifications="hasNotifications"
                @open-navigation="openMobileNavigation"
            />

            <main class="flex-1 p-4 sm:p-5 lg:p-8">
                <div class="mx-auto max-w-[1600px]">
                    <slot />
                </div>
            </main>

            <AppFooter />
        </div>
    </div>
</template>
