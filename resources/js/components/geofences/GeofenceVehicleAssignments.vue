<script setup lang="ts">
import { computed, ref } from 'vue';

import {
    attachVehicleToGeofence,
    detachVehicleFromGeofence,
} from '@/services/geofenceService';
import type { Geofence } from '@/types/geofence';
import type { Vehicle } from '@/types/vehicle';

const props = defineProps<{
    geofence: Geofence;
    vehicles: Vehicle[];
}>();

const emit = defineEmits<{
    updated: [vehicleIds: number[]];
}>();

const processingVehicleId = ref<number | null>(null);
const error = ref<string | null>(null);

const assignedVehicleIds = computed(() => new Set(props.geofence.vehicle_ids));

function isAssigned(vehicleId: number): boolean {
    return assignedVehicleIds.value.has(vehicleId);
}

async function toggleVehicle(vehicle: Vehicle): Promise<void> {
    if (processingVehicleId.value !== null) {
        return;
    }

    processingVehicleId.value = vehicle.id;
    error.value = null;

    try {
        if (isAssigned(vehicle.id)) {
            await detachVehicleFromGeofence(props.geofence.id, vehicle.id);

            emit(
                'updated',
                props.geofence.vehicle_ids.filter(
                    (vehicleId) => vehicleId !== vehicle.id,
                ),
            );

            return;
        }

        await attachVehicleToGeofence(props.geofence.id, vehicle.id);

        emit('updated', [...props.geofence.vehicle_ids, vehicle.id]);
    } catch (caughtError: unknown) {
        error.value =
            caughtError instanceof Error
                ? caughtError.message
                : 'Unable to update vehicle assignment.';
    } finally {
        processingVehicleId.value = null;
    }
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-sm font-medium">Assigned vehicles</h3>
            <p class="text-muted-foreground text-sm">
                Select which vehicles should be associated with this geofence.
            </p>
        </div>

        <p v-if="error" class="text-destructive text-sm">
            {{ error }}
        </p>

        <p v-if="vehicles.length === 0" class="text-muted-foreground text-sm">
            No vehicles are available.
        </p>

        <div v-else class="divide-y rounded-md border">
            <div
                v-for="vehicle in vehicles"
                :key="vehicle.id"
                class="flex items-center justify-between gap-4 p-3"
            >
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium">
                        {{ vehicle.registration_number }}
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex h-8 items-center justify-center rounded-md border px-3 text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50"
                    :disabled="processingVehicleId !== null"
                    @click="toggleVehicle(vehicle)"
                >
                    <template v-if="processingVehicleId === vehicle.id">
                        Saving...
                    </template>

                    <template v-else-if="isAssigned(vehicle.id)">
                        Remove
                    </template>

                    <template v-else> Assign </template>
                </button>
            </div>
        </div>
    </div>
</template>
