<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import AppLogo from '@/components/app/AppLogo.vue';
import AppIcon from '@/components/ui/AppIcon.vue';
import web from '@/routes/web';

type NavigationIcon =
    | 'dashboard'
    | 'fleets'
    | 'vehicles'
    | 'drivers'
    | 'devices'
    | 'tracking'
    | 'geofences'
    | 'alerts'
    | 'reports';

type NavigationItem = {
    name: string;
    icon: NavigationIcon;
    href?: string;
};

const props = withDefaults(
    defineProps<{
        activeItem?: string;
        userName?: string;
        userRole?: string;
        userInitials?: string;
        mobile?: boolean;
    }>(),
    {
        activeItem: undefined,
        userName: undefined,
        userRole: undefined,
        userInitials: undefined,
        mobile: false,
    },
);

const emit = defineEmits<{
    close: [];
}>();

const navigation: readonly NavigationItem[] = [
    {
        name: 'Dashboard',
        icon: 'dashboard',
        href: web.dashboard.url(),
    },
    {
        name: 'Fleets',
        icon: 'fleets',
        href: web.fleets.index.url(),
    },
    { name: 'Vehicles', icon: 'vehicles' },
    { name: 'Drivers', icon: 'drivers' },
    { name: 'Devices', icon: 'devices' },
    { name: 'Live Tracking', icon: 'tracking' },
    { name: 'Geofences', icon: 'geofences' },
    { name: 'Alerts', icon: 'alerts' },
    { name: 'Reports', icon: 'reports' },
];

function handleNavigation(): void {
    if (props.mobile) {
        emit('close');
    }
}
</script>

<template>
    <aside
        class="flex h-full w-64 flex-col bg-sidebar"
        :class="mobile ? '' : 'fixed inset-y-0 left-0 z-40 hidden lg:flex'"
    >
        <div
            class="flex h-20 shrink-0 items-center border-b border-white/10 px-6"
        >
            <AppLogo />

            <button
                v-if="mobile"
                type="button"
                class="ml-auto flex h-9 w-9 items-center justify-center rounded-lg text-subtle transition hover:bg-white/10 hover:text-white"
                aria-label="Close navigation"
                @click="emit('close')"
            >
                <span class="text-2xl leading-none" aria-hidden="true">
                    &times;
                </span>
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-6">
            <template v-for="item in navigation" :key="item.name">
                <Link
                    v-if="item.href"
                    :href="item.href"
                    class="group flex h-10 items-center rounded-lg px-3 text-sm font-medium transition"
                    :class="
                        item.name === activeItem
                            ? 'bg-brand text-sidebar'
                            : 'text-subtle hover:bg-white/5 hover:text-white'
                    "
                    @click="handleNavigation"
                >
                    <AppIcon
                        :name="item.icon"
                        class="mr-3 h-[18px] w-[18px] shrink-0"
                    />

                    {{ item.name }}
                </Link>

                <div
                    v-else
                    class="flex h-10 cursor-not-allowed items-center rounded-lg px-3 text-sm font-medium text-subtle opacity-40"
                    :aria-label="`${item.name} page not available yet`"
                >
                    <AppIcon
                        :name="item.icon"
                        class="mr-3 h-[18px] w-[18px] shrink-0"
                    />

                    {{ item.name }}
                </div>
            </template>
        </nav>

        <div v-if="userName" class="shrink-0 border-t border-white/10 p-4">
            <div class="flex items-center rounded-lg px-2 py-2">
                <div
                    v-if="userInitials"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-xs font-semibold text-brand"
                >
                    {{ userInitials }}
                </div>

                <div class="ml-3 min-w-0">
                    <div class="truncate text-sm font-medium text-white">
                        {{ userName }}
                    </div>

                    <div v-if="userRole" class="truncate text-xs text-subtle">
                        {{ userRole }}
                    </div>
                </div>

                <AppIcon
                    name="chevron-right"
                    class="ml-auto h-4 w-4 shrink-0 text-subtle"
                />
            </div>
        </div>
    </aside>
</template>
