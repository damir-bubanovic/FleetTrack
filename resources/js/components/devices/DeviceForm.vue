<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';

import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import FormField from '@/components/ui/FormField.vue';
import { ApiError } from '@/services/apiClient';
import { authState } from '@/services/authState';
import { getCompanies } from '@/services/companyService';
import { createDevice, updateDevice } from '@/services/deviceService';
import type { UpdateDevicePayload } from '@/services/deviceService';
import type { Company } from '@/types/company';
import type { Device, DeviceStatus } from '@/types/device';
import type { Vehicle } from '@/types/vehicle';

const props = defineProps<{
    device?: Device;
    vehicles: Vehicle[];
}>();

const emit = defineEmits<{
    saved: [device: Device];
    cancel: [];
}>();

const editing = computed(() => props.device !== undefined);
const isSuperAdmin = computed(
    () => authState.user.value?.roles.includes('super_admin') ?? false,
);

const companies = ref<Company[]>([]);

const companyOptions = computed(() =>
    companies.value.map((company) => ({
        value: company.id,
        label: company.name,
    })),
);

const vehicleOptions = computed(() => [
    {
        value: '',
        label: 'Unassigned',
    },
    ...props.vehicles.map((vehicle) => ({
        value: vehicle.id,
        label: `${vehicle.manufacturer} ${vehicle.model} (${vehicle.registration_number})`,
    })),
]);

const statusOptions: { value: DeviceStatus; label: string }[] = [
    {
        value: 'active',
        label: 'Active',
    },
    {
        value: 'inactive',
        label: 'Inactive',
    },
    {
        value: 'offline',
        label: 'Offline',
    },
];

const form = reactive<{
    company_id: number | string | null;
    vehicle_id: number | string | null;
    name: string;
    unique_id: string;
    status: DeviceStatus;
}>({
    company_id: props.device?.company_id ?? null,
    vehicle_id: props.device?.vehicle_id ?? null,
    name: props.device?.name ?? '',
    unique_id: props.device?.unique_id ?? '',
    status: props.device?.status ?? 'active',
});

type FormFieldName = keyof typeof form;

const submitting = ref(false);
const error = ref<string | null>(null);
const validationErrors = ref<Record<string, string[]>>({});

function fieldError(field: FormFieldName): string | null {
    return validationErrors.value[field]?.[0] ?? null;
}

function clearFieldError(field: FormFieldName): void {
    if (!(field in validationErrors.value)) {
        return;
    }

    const errors = { ...validationErrors.value };

    delete errors[field];

    validationErrors.value = errors;
}

for (const field of Object.keys(form) as FormFieldName[]) {
    watch(
        () => form[field],
        () => {
            clearFieldError(field);
        },
    );
}

async function loadCompanies(): Promise<void> {
    if (!isSuperAdmin.value) {
        return;
    }

    try {
        const response = await getCompanies();

        companies.value = response.data;
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load companies.';
    }
}

async function submit(): Promise<void> {
    const vehicleId =
        form.vehicle_id === null || form.vehicle_id === ''
            ? null
            : Number(form.vehicle_id);

    const companyId =
        form.company_id === null || form.company_id === ''
            ? null
            : Number(form.company_id);

    if (isSuperAdmin.value && vehicleId === null && companyId === null) {
        validationErrors.value = {
            company_id: [
                'The company field is required when no vehicle is assigned.',
            ],
        };

        return;
    }

    submitting.value = true;
    error.value = null;
    validationErrors.value = {};

    const payload: UpdateDevicePayload = {
        vehicle_id: vehicleId,
        name: form.name.trim(),
        unique_id: form.unique_id.trim(),
        status: form.status,
    };

    if (isSuperAdmin.value && companyId !== null) {
        payload.company_id = companyId;
    }

    try {
        const device = props.device
            ? await updateDevice(props.device, payload)
            : await createDevice(payload);

        emit('saved', device);
    } catch (exception) {
        if (exception instanceof ApiError) {
            validationErrors.value = exception.errors;

            if (Object.keys(exception.errors).length === 0) {
                error.value = exception.message;
            }

            return;
        }

        error.value = editing.value
            ? 'Unable to update device.'
            : 'Unable to create device.';
    } finally {
        submitting.value = false;
    }
}

onMounted(loadCompanies);
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
            <FormField
                v-if="isSuperAdmin"
                label="Company"
                input-id="device-company"
                :error="fieldError('company_id')"
            >
                <AppSelect
                    id="device-company"
                    v-model="form.company_id"
                    :options="companyOptions"
                    placeholder="Select company"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Vehicle"
                input-id="device-vehicle"
                :error="fieldError('vehicle_id')"
            >
                <AppSelect
                    id="device-vehicle"
                    v-model="form.vehicle_id"
                    :options="vehicleOptions"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Status"
                input-id="device-status"
                :error="fieldError('status')"
                required
            >
                <AppSelect
                    id="device-status"
                    v-model="form.status"
                    :options="statusOptions"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Device name"
                input-id="device-name"
                :error="fieldError('name')"
                required
            >
                <AppInput
                    id="device-name"
                    v-model="form.name"
                    placeholder="Vehicle GPS"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Unique ID"
                input-id="device-unique-id"
                :error="fieldError('unique_id')"
                required
            >
                <AppInput
                    id="device-unique-id"
                    v-model="form.unique_id"
                    placeholder="IMEI or device identifier"
                    required
                    :disabled="submitting"
                />
            </FormField>
        </div>

        <div
            v-if="editing"
            class="rounded-lg border border-border-default bg-surface-muted px-4 py-3"
        >
            <div class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium text-muted">
                        Traccar device ID
                    </p>

                    <p class="mt-1 font-medium text-content">
                        {{ device?.traccar_device_id ?? 'Not synchronized' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-muted">
                        Last synchronized
                    </p>

                    <p class="mt-1 font-medium text-content">
                        {{ device?.last_sync_at ?? 'Never' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="border-border flex justify-end gap-3 border-t pt-5">
            <AppButton
                type="button"
                variant="secondary"
                :disabled="submitting"
                @click="emit('cancel')"
            >
                Cancel
            </AppButton>

            <AppButton type="submit" :loading="submitting">
                {{ editing ? 'Save changes' : 'Create device' }}
            </AppButton>
        </div>
    </form>
</template>
