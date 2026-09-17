<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import DashboardMetricCard from '@/components/dashboard/DashboardMetricCard.vue';
import DeviceConnectivity from '@/components/dashboard/DeviceConnectivity.vue';
import FleetStatusTable from '@/components/dashboard/FleetStatusTable.vue';
import RecentAlerts from '@/components/dashboard/RecentAlerts.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const fleetMetrics = [
    {
        label: 'Vehicles',
        value: 48,
        detail: 'Across 6 fleets',
        icon: 'vehicles',
    },
    {
        label: 'Online',
        value: 41,
        detail: '85% of vehicles',
        icon: 'online',
    },
    {
        label: 'Offline',
        value: 7,
        detail: 'Requires attention',
        icon: 'offline',
    },
    {
        label: 'Unacknowledged alerts',
        value: 5,
        detail: '12 alerts total',
        icon: 'alerts',
    },
] as const;

const fleetStatus = [
    {
        name: 'Zagreb Distribution',
        vehicles: 14,
        online: 13,
        offline: 1,
    },
    {
        name: 'Regional Delivery',
        vehicles: 12,
        online: 11,
        offline: 1,
    },
    {
        name: 'Long Haul',
        vehicles: 10,
        online: 8,
        offline: 2,
    },
    {
        name: 'City Logistics',
        vehicles: 8,
        online: 7,
        offline: 1,
    },
    {
        name: 'Service Fleet',
        vehicles: 4,
        online: 2,
        offline: 2,
    },
];

const recentAlerts = [
    {
        title: 'Vehicle entered restricted geofence',
        vehicle: 'ZG-4821-FT',
        time: '8 min ago',
        severity: 'Critical',
    },
    {
        title: 'Vehicle offline',
        vehicle: 'ZG-9134-KL',
        time: '24 min ago',
        severity: 'Warning',
    },
    {
        title: 'Vehicle exited warehouse geofence',
        vehicle: 'ZG-2287-FT',
        time: '42 min ago',
        severity: 'Info',
    },
] as const;

function refreshDashboard(): void {
    //
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout
        title="Dashboard"
        description="Fleet operations overview"
        active-navigation="Dashboard"
        company-name="FleetTrack Logistics"
        company-location="Zagreb, Croatia"
        user-name="Damir Bubanovic"
        user-role="Administrator"
        user-initials="DB"
        has-notifications
    >
        <PageHeader
            eyebrow="Operations"
            title="Fleet overview"
            description="Current status across your fleet and tracking devices."
            show-live-status
            show-refresh
            @refresh="refreshDashboard"
        />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <DashboardMetricCard
                v-for="metric in fleetMetrics"
                :key="metric.label"
                :label="metric.label"
                :value="metric.value"
                :detail="metric.detail"
                :icon="metric.icon"
            />
        </section>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1.65fr_1fr]">
            <FleetStatusTable :fleets="fleetStatus" />

            <RecentAlerts :alerts="recentAlerts" />
        </div>

        <DeviceConnectivity :devices="52" :online="47" :offline="5" />
    </AppLayout>
</template>
