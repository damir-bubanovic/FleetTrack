<script setup lang="ts">
import { computed, reactive, ref } from 'vue';

import AppButton from '@/components/ui/AppButton.vue';
import AppCheckbox from '@/components/ui/AppCheckbox.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import AppTextarea from '@/components/ui/AppTextarea.vue';
import FormField from '@/components/ui/FormField.vue';
import { createVehicle, updateVehicle } from '@/services/vehicleService';
import type {
    CreateVehiclePayload,
    UpdateVehiclePayload,
} from '@/services/vehicleService';
import type { Fleet } from '@/types/fleet';
import type { Vehicle } from '@/types/vehicle';

const props = defineProps<{
    vehicle?: Vehicle;
    fleets: Fleet[];
}>();

const emit = defineEmits<{
    saved: [vehicle: Vehicle];
    cancel: [];
}>();

const editing = computed(() => props.vehicle !== undefined);

const fleetOptions = computed(() =>
    props.fleets.map((fleet) => ({
        value: fleet.id,
        label: `${fleet.name} (${fleet.code})`,
    })),
);

const form = reactive({
    fleet_id: props.vehicle?.fleet_id ?? props.fleets[0]?.id ?? null,
    registration_number: props.vehicle?.registration_number ?? '',
    vin: props.vehicle?.vin ?? '',
    manufacturer: props.vehicle?.manufacturer ?? '',
    model: props.vehicle?.model ?? '',
    year: props.vehicle ? String(props.vehicle.year) : '',
    color: props.vehicle?.color ?? '',
    fuel_type: props.vehicle?.fuel_type ?? '',
    transmission: props.vehicle?.transmission ?? '',
    odometer:
        props.vehicle?.odometer === null ||
        props.vehicle?.odometer === undefined
            ? ''
            : String(props.vehicle.odometer),
    notes: props.vehicle?.notes ?? '',
    is_active: props.vehicle?.is_active ?? true,
});

const submitting = ref(false);
const error = ref<string | null>(null);

const fuelTypeOptions = [
    { value: 'petrol', label: 'Petrol' },
    { value: 'diesel', label: 'Diesel' },
    { value: 'electric', label: 'Electric' },
    { value: 'hybrid', label: 'Hybrid' },
];

const transmissionOptions = [
    { value: 'manual', label: 'Manual' },
    { value: 'automatic', label: 'Automatic' },
];

function optionalString(value: string): string | null {
    const trimmedValue = value.trim();

    return trimmedValue === '' ? null : trimmedValue;
}

function optionalNumber(value: string): number | null {
    const trimmedValue = value.trim();

    return trimmedValue === '' ? null : Number(trimmedValue);
}

async function submit(): Promise<void> {
    if (form.fleet_id === null) {
        error.value = 'Please select a fleet.';

        return;
    }

    submitting.value = true;
    error.value = null;

    const payload: CreateVehiclePayload | UpdateVehiclePayload = {
        fleet_id: Number(form.fleet_id),
        registration_number: form.registration_number.trim(),
        vin: form.vin.trim(),
        manufacturer: form.manufacturer.trim(),
        model: form.model.trim(),
        year: Number(form.year),
        color: optionalString(form.color),
        fuel_type: form.fuel_type,
        transmission: form.transmission,
        odometer: optionalNumber(form.odometer),
        notes: optionalString(form.notes),
        is_active: form.is_active,
    };

    try {
        const vehicle = props.vehicle
            ? await updateVehicle(props.vehicle, payload)
            : await createVehicle(payload);

        emit('saved', vehicle);
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : editing.value
                  ? 'Unable to update vehicle.'
                  : 'Unable to create vehicle.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <form class="space-y-5" @submit.prevent="submit">
        <div
            v-if="error"
            class="rounded-lg border border-danger/30 bg-danger/5 px-4 py-3 text-sm text-danger-dark"
            role="alert"
        >
            {{ error }}
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <FormField label="Fleet" input-id="vehicle-fleet" required>
                <AppSelect
                    id="vehicle-fleet"
                    v-model="form.fleet_id"
                    :options="fleetOptions"
                    placeholder="Select fleet"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Registration number"
                input-id="vehicle-registration-number"
                required
            >
                <AppInput
                    id="vehicle-registration-number"
                    v-model="form.registration_number"
                    placeholder="ZG 1234 AB"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="VIN"
                input-id="vehicle-vin"
                description="The vehicle identification number must contain exactly 17 characters."
                required
            >
                <AppInput
                    id="vehicle-vin"
                    v-model="form.vin"
                    minlength="17"
                    maxlength="17"
                    placeholder="WVWZZZ1JZXW000001"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Manufacturer"
                input-id="vehicle-manufacturer"
                required
            >
                <AppInput
                    id="vehicle-manufacturer"
                    v-model="form.manufacturer"
                    placeholder="Volkswagen"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField label="Model" input-id="vehicle-model" required>
                <AppInput
                    id="vehicle-model"
                    v-model="form.model"
                    placeholder="Transporter"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField label="Year" input-id="vehicle-year" required>
                <AppInput
                    id="vehicle-year"
                    v-model="form.year"
                    type="number"
                    min="1900"
                    step="1"
                    placeholder="2024"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField label="Color" input-id="vehicle-color">
                <AppInput
                    id="vehicle-color"
                    v-model="form.color"
                    placeholder="White"
                    :disabled="submitting"
                />
            </FormField>

            <FormField label="Fuel type" input-id="vehicle-fuel-type" required>
                <AppSelect
                    id="vehicle-fuel-type"
                    v-model="form.fuel_type"
                    :options="fuelTypeOptions"
                    placeholder="Select fuel type"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Transmission"
                input-id="vehicle-transmission"
                required
            >
                <AppSelect
                    id="vehicle-transmission"
                    v-model="form.transmission"
                    :options="transmissionOptions"
                    placeholder="Select transmission"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField label="Odometer" input-id="vehicle-odometer">
                <AppInput
                    id="vehicle-odometer"
                    v-model="form.odometer"
                    type="number"
                    min="0"
                    step="1"
                    placeholder="125000"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                class="md:col-span-2"
                label="Notes"
                input-id="vehicle-notes"
            >
                <AppTextarea
                    id="vehicle-notes"
                    v-model="form.notes"
                    placeholder="Optional notes about this vehicle"
                    :disabled="submitting"
                />
            </FormField>
        </div>

        <label
            class="flex items-start gap-3 rounded-lg border border-border-default bg-surface-muted px-4 py-3"
        >
            <AppCheckbox
                v-model="form.is_active"
                :disabled="submitting"
                class="mt-0.5"
            />

            <span>
                <span class="block text-sm font-medium text-content">
                    Active vehicle
                </span>

                <span class="mt-0.5 block text-xs text-muted">
                    Active vehicles are available for normal fleet operations.
                </span>
            </span>
        </label>

        <div class="flex justify-end gap-3 border-t border-border-default pt-5">
            <AppButton
                variant="secondary"
                :disabled="submitting"
                @click="emit('cancel')"
            >
                Cancel
            </AppButton>

            <AppButton type="submit" :loading="submitting">
                {{ editing ? 'Save changes' : 'Create vehicle' }}
            </AppButton>
        </div>
    </form>
</template>
