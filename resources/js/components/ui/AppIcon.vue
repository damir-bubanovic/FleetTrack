<script setup lang="ts">
type IconName =
    | 'dashboard'
    | 'fleets'
    | 'vehicles'
    | 'drivers'
    | 'devices'
    | 'tracking'
    | 'geofences'
    | 'alerts'
    | 'reports'
    | 'online'
    | 'offline'
    | 'refresh'
    | 'menu'
    | 'chevron-right'
    | 'notification';

defineProps<{
    name: IconName;
}>();

const iconPaths: Record<IconName, string[]> = {
    dashboard: [
        'M3 3h7v7H3z',
        'M14 3h7v7h-7z',
        'M3 14h7v7H3z',
        'M14 14h7v7h-7z',
    ],
    fleets: [
        'M3 7h18',
        'M5 7l2-4h10l2 4',
        'M5 7v11h14V7',
        'M8 18v3',
        'M16 18v3',
    ],
    vehicles: [
        'M3 15V9l2-4h14l2 4v6',
        'M5 15h14',
        'M7 18h.01',
        'M17 18h.01',
        'M5 11h14',
    ],
    drivers: [
        'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
        'M4 21a8 8 0 0 1 16 0',
    ],
    devices: [
        'M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z',
        'M10 18h4',
    ],
    tracking: [
        'M12 21s7-5.5 7-12a7 7 0 1 0-14 0c0 6.5 7 12 7 12Z',
        'M12 11a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z',
    ],
    geofences: [
        'M4 6l8-4 8 4v12l-8 4-8-4Z',
        'M12 2v20',
        'M4 6l8 4 8-4',
    ],
    alerts: [
        'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9',
        'M10 21h4',
    ],
    reports: [
        'M4 19V9',
        'M10 19V5',
        'M16 19v-7',
        'M22 19H2',
    ],
    online: ['M5 12l4 4L19 6'],
    offline: ['M6 6l12 12', 'M18 6 6 18'],
    refresh: ['M20 11a8 8 0 1 0-2.34 5.66', 'M20 4v7h-7'],
    menu: ['M4 6h16', 'M4 12h16', 'M4 18h16'],
    'chevron-right': ['m9 18 6-6-6-6'],
    notification: [
        'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9',
        'M10 21h4',
    ],
};
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path v-for="path in iconPaths[name]" :key="path" :d="path" />
    </svg>
</template>