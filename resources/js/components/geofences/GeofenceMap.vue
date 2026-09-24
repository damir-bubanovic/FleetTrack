<script setup lang="ts">
import L from 'leaflet';
import type { LatLngExpression, Map as LeafletMap } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import type { Geofence } from '@/types/geofence';

const props = withDefaults(
    defineProps<{
        geofences: Geofence[];
        selectedGeofenceId?: number | null;
    }>(),
    {
        selectedGeofenceId: null,
    },
);

const emit = defineEmits<{
    select: [geofence: Geofence];
}>();

const mapElement = ref<HTMLDivElement | null>(null);

let map: LeafletMap | null = null;
let geofenceLayer: L.FeatureGroup | null = null;

const defaultCenter: LatLngExpression = [45.815, 15.9819];
const defaultZoom = 12;

function parseCoordinates(value: string): LatLngExpression[] {
    return value
        .split(',')
        .map((coordinate) => coordinate.trim())
        .filter(Boolean)
        .map((coordinate) => {
            const [latitude, longitude] = coordinate.split(/\s+/).map(Number);

            return [latitude, longitude] as LatLngExpression;
        })
        .filter((coordinate) => {
            const latLng = L.latLng(coordinate);

            return Number.isFinite(latLng.lat) && Number.isFinite(latLng.lng);
        });
}

function createLayer(geofence: Geofence): L.Layer | null {
    const area = geofence.area.trim();

    const circleMatch = area.match(
        /^CIRCLE\s*\(\s*([+-]?\d+(?:\.\d+)?)\s+([+-]?\d+(?:\.\d+)?)\s*,\s*([+-]?\d+(?:\.\d+)?)\s*\)$/i,
    );

    if (circleMatch) {
        const latitude = Number(circleMatch[1]);
        const longitude = Number(circleMatch[2]);
        const radius = Number(circleMatch[3]);

        if (
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude) ||
            !Number.isFinite(radius)
        ) {
            return null;
        }

        return L.circle([latitude, longitude], {
            radius,
        });
    }

    const polygonMatch = area.match(/^POLYGON\s*\(\((.+)\)\)$/i);

    if (polygonMatch) {
        const coordinates = parseCoordinates(polygonMatch[1]);

        if (coordinates.length < 3) {
            return null;
        }

        return L.polygon(coordinates);
    }

    const lineStringMatch = area.match(/^LINESTRING\s*\((.+)\)$/i);

    if (lineStringMatch) {
        const coordinates = parseCoordinates(lineStringMatch[1]);

        if (coordinates.length < 2) {
            return null;
        }

        return L.polyline(coordinates);
    }

    return null;
}

function renderGeofences(): void {
    if (!map || !geofenceLayer) {
        return;
    }

    geofenceLayer.clearLayers();

    for (const geofence of props.geofences) {
        const layer = createLayer(geofence);

        if (!layer) {
            continue;
        }

        layer.bindTooltip(geofence.name);

        layer.on('click', () => {
            emit('select', geofence);
        });

        layer.addTo(geofenceLayer);
    }

    const bounds = geofenceLayer.getBounds();

    if (bounds.isValid()) {
        map.fitBounds(bounds, {
            padding: [30, 30],
            maxZoom: 15,
        });
    } else {
        map.setView(defaultCenter, defaultZoom);
    }
}

function focusSelectedGeofence(): void {
    if (!map || props.selectedGeofenceId === null) {
        return;
    }

    const geofence = props.geofences.find(
        (item) => item.id === props.selectedGeofenceId,
    );

    if (!geofence) {
        return;
    }

    const layer = createLayer(geofence);

    if (!layer) {
        return;
    }

    if (layer instanceof L.Circle) {
        map.fitBounds(layer.getBounds(), {
            padding: [40, 40],
            maxZoom: 16,
        });

        return;
    }

    if (layer instanceof L.Polygon || layer instanceof L.Polyline) {
        map.fitBounds(layer.getBounds(), {
            padding: [40, 40],
            maxZoom: 16,
        });
    }
}

onMounted(async () => {
    await nextTick();

    if (!mapElement.value) {
        return;
    }

    map = L.map(mapElement.value).setView(defaultCenter, defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    geofenceLayer = L.featureGroup().addTo(map);

    renderGeofences();
    focusSelectedGeofence();
});

onBeforeUnmount(() => {
    map?.remove();

    map = null;
    geofenceLayer = null;
});

watch(
    () => props.geofences,
    async () => {
        await nextTick();

        renderGeofences();
        focusSelectedGeofence();
    },
    {
        deep: true,
    },
);

watch(
    () => props.selectedGeofenceId,
    async () => {
        await nextTick();

        focusSelectedGeofence();
    },
);
</script>

<template>
    <div
        ref="mapElement"
        class="relative z-0 h-[420px] w-full overflow-hidden rounded-lg border border-border-default"
    />
</template>
