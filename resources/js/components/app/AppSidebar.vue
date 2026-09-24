<script setup lang="ts">
import { router, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import AppLogo from '@/components/app/AppLogo.vue';
import AppIcon from '@/components/ui/AppIcon.vue';
import web from '@/routes/web';
import { authState } from '@/services/authState';

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
        mobile?: boolean;
    }>(),
    {
        activeItem: undefined,
        mobile: false,
    },
);

const emit = defineEmits<{
    close: [];
}>();

const loggingOut = ref(false);

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
    {
        name: 'Vehicles',
        icon: 'vehicles',
        href: web.vehicles.index.url(),
    },
    {
        name: 'Drivers',
        icon: 'drivers',
        href: web.drivers.index.url(),
    },
    {
        name: 'Devices',
        icon: 'devices',
        href: web.devices.index.url(),
    },
    {
        name: 'Live Tracking',
        icon: 'tracking',
        href: web.tracking.index.url(),
    },
    {
        name: 'Geofences',
        icon: 'geofences',
        href: web.geofences.index.url(),
    },
    {
        name: 'Alert Rules',
        icon: 'alerts',
        href: web.alertRules.index.url(),
    },
    { name: 'Reports', icon: 'reports' },
];

const userName = computed(() => authState.user.value?.name ?? '');

const userRole = computed(() => {
    const role = authState.user.value?.roles[0];

    if (!role) {
        return '';
    }

    return role
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
});

const userInitials = computed(() => {
    const name = userName.value.trim();

    if (!name) {
        return '';
    }

    return name
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});

function handleNavigation(): void {
    if (props.mobile) {
        emit('close');
    }
}

async function handleLogout(): Promise<void> {
    if (loggingOut.value) {
        return;
    }

    loggingOut.value = true;

    try {
        await authState.logout();

        emit('close');

        router.visit(web.login.url());
    } finally {
        loggingOut.value = false;
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
            <div class="flex items-center px-2 py-2">
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
            </div>

            <button
                type="button"
                class="mt-2 flex h-9 w-full items-center rounded-lg px-3 text-sm font-medium text-subtle transition hover:bg-white/5 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="loggingOut"
                @click="handleLogout"
            >
                {{ loggingOut ? 'Signing out...' : 'Sign out' }}
            </button>
        </div>
    </aside>
</template>
