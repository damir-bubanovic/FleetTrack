<script setup lang="ts">
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, onBeforeUnmount, onMounted, watch } from 'vue';

import type { HistoricalPosition } from '@/types/tracking';

const props = defineProps<{
    positions: HistoricalPosition[];
}>();

const emit = defineEmits<{
    select: [position: HistoricalPosition];
}>();

let mapElement: HTMLDivElement | null = null;
let map: L.Map | null = null;
let routeLayer: L.Polyline | null = null;
let markersLayer: L.LayerGroup | null = null;

const defaultCenter: L.LatLngExpression = [45.815, 15.9819];

function setMapElement(element: unknown): void {
    mapElement = element instanceof HTMLDivElement ? element : null;
}

function validPositions(): Array<
    HistoricalPosition & {
        latitude: number;
        longitude: number;
    }
> {
    return props.positions.filter(
        (
            position,
        ): position is HistoricalPosition & {
            latitude: number;
            longitude: number;
        } =>
            position.latitude !== null &&
            position.longitude !== null &&
            Number.isFinite(position.latitude) &&
            Number.isFinite(position.longitude),
    );
}

function createMarker(
    position: HistoricalPosition & {
        latitude: number;
        longitude: number;
    },
    label: string,
): L.CircleMarker {
    const marker = L.circleMarker([position.latitude, position.longitude], {
        radius: 7,
        weight: 3,
        fillOpacity: 1,
    });

    marker.bindTooltip(label);
    marker.on('click', () => emit('select', position));

    return marker;
}

function renderRoute(): void {
    if (!map) {
        return;
    }

    if (routeLayer) {
        map.removeLayer(routeLayer);
        routeLayer = null;
    }

    if (markersLayer) {
        map.removeLayer(markersLayer);
        markersLayer = null;
    }

    const positions = validPositions();

    if (positions.length === 0) {
        map.setView(defaultCenter, 12);

        return;
    }

    const coordinates = positions.map(
        (position) => [position.latitude, position.longitude] as L.LatLngTuple,
    );

    routeLayer = L.polyline(coordinates, {
        weight: 4,
        opacity: 0.8,
    }).addTo(map);

    markersLayer = L.layerGroup().addTo(map);

    const startPosition = positions[0];
    const endPosition = positions[positions.length - 1];

    createMarker(startPosition, 'Route start').addTo(markersLayer);

    if (endPosition !== startPosition) {
        createMarker(endPosition, 'Route end').addTo(markersLayer);
    }

    if (positions.length === 1) {
        map.setView(coordinates[0], 15);

        return;
    }

    map.fitBounds(routeLayer.getBounds(), {
        padding: [32, 32],
    });
}

onMounted(async () => {
    await nextTick();

    if (!mapElement) {
        return;
    }

    map = L.map(mapElement, {
        zoomControl: true,
    }).setView(defaultCenter, 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    renderRoute();
});

watch(
    () => props.positions,
    async () => {
        await nextTick();

        renderRoute();
        map?.invalidateSize();
    },
    {
        deep: true,
    },
);

onBeforeUnmount(() => {
    map?.remove();
    map = null;
    routeLayer = null;
    markersLayer = null;
});
</script>

<template>
    <div
        :ref="setMapElement"
        class="relative z-0 h-[28rem] w-full overflow-hidden rounded-lg"
    />
</template>
