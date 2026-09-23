<script setup lang="ts">
import L from 'leaflet';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

import type { LivePosition } from '@/types/tracking';

const props = defineProps<{
    positions: LivePosition[];
}>();

const mapElement = ref<HTMLDivElement | null>(null);

let map: L.Map | null = null;
let markerLayer: L.LayerGroup | null = null;

const defaultCenter: L.LatLngExpression = [45.815, 15.9819];
const defaultZoom = 11;

function createMarkerIcon(online: boolean): L.DivIcon {
    return L.divIcon({
        className: '',
        html: `
            <div class="tracking-marker ${
                online ? 'tracking-marker--online' : 'tracking-marker--offline'
            }">
                <span></span>
            </div>
        `,
        iconSize: [22, 22],
        iconAnchor: [11, 11],
        popupAnchor: [0, -13],
    });
}

function renderPositions(): void {
    if (!map || !markerLayer) {
        return;
    }

    const currentMap = map;
    const currentMarkerLayer = markerLayer;

    currentMarkerLayer.clearLayers();

    const bounds = L.latLngBounds([]);

    props.positions.forEach((item) => {
        const { latitude, longitude } = item.position;

        if (
            latitude === null ||
            longitude === null ||
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude)
        ) {
            return;
        }

        const marker = L.marker([latitude, longitude], {
            icon: createMarkerIcon(item.status.online),
        });

        const vehicleName = item.vehicle?.name ?? item.device.name ?? 'Vehicle';

        const deviceName =
            item.device.name ?? item.device.unique_id ?? 'Unknown device';

        const speed =
            item.position.speed !== null ? `${item.position.speed} kn` : '—';

        const popup = document.createElement('div');
        popup.className = 'tracking-popup';

        const title = document.createElement('strong');
        title.textContent = vehicleName;

        const device = document.createElement('div');
        device.textContent = deviceName;

        const status = document.createElement('div');
        status.textContent = item.status.online ? 'Online' : 'Offline';

        const speedElement = document.createElement('div');
        speedElement.textContent = `Speed: ${speed}`;

        popup.append(title, device, status, speedElement);

        marker.bindPopup(popup);

        marker.addTo(currentMarkerLayer);
        bounds.extend([latitude, longitude]);
    });

    if (bounds.isValid()) {
        currentMap.fitBounds(bounds, {
            padding: [40, 40],
            maxZoom: 14,
        });

        return;
    }

    currentMap.setView(defaultCenter, defaultZoom);
}

onMounted(() => {
    if (!mapElement.value) {
        return;
    }

    map = L.map(mapElement.value, {
        zoomControl: true,
    }).setView(defaultCenter, defaultZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution:
            '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    markerLayer = L.layerGroup().addTo(map);

    renderPositions();
});

watch(
    () => props.positions,
    () => {
        renderPositions();
    },
    {
        deep: true,
    },
);

onBeforeUnmount(() => {
    map?.remove();

    markerLayer = null;
    map = null;
});
</script>

<template>
    <div
        ref="mapElement"
        class="h-[520px] w-full overflow-hidden rounded-xl"
        aria-label="Live vehicle tracking map"
    />
</template>

<style>
@import 'leaflet/dist/leaflet.css';

.tracking-marker {
    display: flex;
    width: 22px;
    height: 22px;
    align-items: center;
    justify-content: center;
    border: 3px solid white;
    border-radius: 9999px;
    box-shadow: 0 2px 8px rgb(0 0 0 / 35%);
}

.tracking-marker span {
    width: 8px;
    height: 8px;
    border-radius: 9999px;
    background: currentColor;
}

.tracking-marker--online {
    color: #22c55e;
    background: #22c55e;
}

.tracking-marker--offline {
    color: #64748b;
    background: #64748b;
}

.tracking-popup {
    min-width: 130px;
    line-height: 1.5;
}

.tracking-popup strong {
    display: block;
    margin-bottom: 4px;
}
</style>
