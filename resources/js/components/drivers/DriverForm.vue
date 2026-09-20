<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';

import AppButton from '@/components/ui/AppButton.vue';
import AppCheckbox from '@/components/ui/AppCheckbox.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import AppTextarea from '@/components/ui/AppTextarea.vue';
import FormField from '@/components/ui/FormField.vue';
import { ApiError } from '@/services/apiClient';
import { createDriver, updateDriver } from '@/services/driverService';
import type {
    CreateDriverPayload,
    UpdateDriverPayload,
} from '@/services/driverService';
import type { Driver } from '@/types/driver';
import type { Fleet } from '@/types/fleet';

const props = defineProps<{
    driver?: Driver;
    fleets: Fleet[];
}>();

const emit = defineEmits<{
    saved: [driver: Driver];
    cancel: [];
}>();

const editing = computed(() => props.driver !== undefined);

const fleetOptions = computed(() =>
    props.fleets.map((fleet) => ({
        value: fleet.id,
        label: `${fleet.name} (${fleet.code})`,
    })),
);

function dateInputValue(value: string | null | undefined): string {
    return value?.slice(0, 10) ?? '';
}

const form = reactive({
    fleet_id: props.driver?.fleet_id ?? props.fleets[0]?.id ?? null,
    employee_number: props.driver?.employee_number ?? '',
    first_name: props.driver?.first_name ?? '',
    last_name: props.driver?.last_name ?? '',
    phone: props.driver?.phone ?? '',
    email: props.driver?.email ?? '',
    license_number: props.driver?.license_number ?? '',
    license_category: props.driver?.license_category ?? '',
    license_expiry_date: dateInputValue(props.driver?.license_expiry_date),
    employment_date: dateInputValue(props.driver?.employment_date),
    notes: props.driver?.notes ?? '',
    is_active: props.driver?.is_active ?? true,
});

type FormFieldName = keyof typeof form;

const submitting = ref(false);
const error = ref<string | null>(null);
const validationErrors = ref<Record<string, string[]>>({});

function optionalString(value: string): string | null {
    const trimmedValue = value.trim();

    return trimmedValue === '' ? null : trimmedValue;
}

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

async function submit(): Promise<void> {
    if (form.fleet_id === null) {
        validationErrors.value = {
            fleet_id: ['The fleet field is required.'],
        };

        return;
    }

    submitting.value = true;
    error.value = null;
    validationErrors.value = {};

    const payload: CreateDriverPayload | UpdateDriverPayload = {
        fleet_id: Number(form.fleet_id),
        employee_number: form.employee_number.trim(),
        first_name: form.first_name.trim(),
        last_name: form.last_name.trim(),
        phone: optionalString(form.phone),
        email: optionalString(form.email),
        license_number: form.license_number.trim(),
        license_category: form.license_category.trim(),
        license_expiry_date: form.license_expiry_date,
        employment_date: form.employment_date,
        notes: optionalString(form.notes),
        is_active: form.is_active,
    };

    try {
        const driver = props.driver
            ? await updateDriver(props.driver, payload)
            : await createDriver(payload);

        emit('saved', driver);
    } catch (exception) {
        if (exception instanceof ApiError) {
            validationErrors.value = exception.errors;

            if (Object.keys(exception.errors).length === 0) {
                error.value = exception.message;
            }

            return;
        }

        error.value = editing.value
            ? 'Unable to update driver.'
            : 'Unable to create driver.';
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
            <FormField
                label="Fleet"
                input-id="driver-fleet"
                :error="fieldError('fleet_id')"
                required
            >
                <AppSelect
                    id="driver-fleet"
                    v-model="form.fleet_id"
                    :options="fleetOptions"
                    placeholder="Select fleet"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Employee number"
                input-id="driver-employee-number"
                :error="fieldError('employee_number')"
                required
            >
                <AppInput
                    id="driver-employee-number"
                    v-model="form.employee_number"
                    maxlength="50"
                    placeholder="DRV-001"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="First name"
                input-id="driver-first-name"
                :error="fieldError('first_name')"
                required
            >
                <AppInput
                    id="driver-first-name"
                    v-model="form.first_name"
                    autocomplete="given-name"
                    placeholder="Ivan"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Last name"
                input-id="driver-last-name"
                :error="fieldError('last_name')"
                required
            >
                <AppInput
                    id="driver-last-name"
                    v-model="form.last_name"
                    autocomplete="family-name"
                    placeholder="Horvat"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Email"
                input-id="driver-email"
                :error="fieldError('email')"
            >
                <AppInput
                    id="driver-email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    placeholder="ivan@example.com"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Phone"
                input-id="driver-phone"
                :error="fieldError('phone')"
            >
                <AppInput
                    id="driver-phone"
                    v-model="form.phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="+385 91 123 4567"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="License number"
                input-id="driver-license-number"
                :error="fieldError('license_number')"
                required
            >
                <AppInput
                    id="driver-license-number"
                    v-model="form.license_number"
                    maxlength="100"
                    placeholder="12345678"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="License category"
                input-id="driver-license-category"
                :error="fieldError('license_category')"
                required
            >
                <AppInput
                    id="driver-license-category"
                    v-model="form.license_category"
                    maxlength="20"
                    placeholder="B"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="License expiry date"
                input-id="driver-license-expiry-date"
                :error="fieldError('license_expiry_date')"
                required
            >
                <AppInput
                    id="driver-license-expiry-date"
                    v-model="form.license_expiry_date"
                    type="date"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Employment date"
                input-id="driver-employment-date"
                :error="fieldError('employment_date')"
                required
            >
                <AppInput
                    id="driver-employment-date"
                    v-model="form.employment_date"
                    type="date"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                class="md:col-span-2"
                label="Notes"
                input-id="driver-notes"
                :error="fieldError('notes')"
            >
                <AppTextarea
                    id="driver-notes"
                    v-model="form.notes"
                    placeholder="Additional information about this driver"
                    :disabled="submitting"
                />
            </FormField>

            <div class="md:col-span-2">
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
                            Active driver
                        </span>

                        <span class="mt-0.5 block text-xs text-muted">
                            Active drivers are available for normal fleet
                            operations.
                        </span>
                    </span>
                </label>

                <p
                    v-if="fieldError('is_active')"
                    class="mt-1.5 text-xs font-medium text-danger-dark"
                    role="alert"
                >
                    {{ fieldError('is_active') }}
                </p>
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
                {{ editing ? 'Save changes' : 'Create driver' }}
            </AppButton>
        </div>
    </form>
</template>
