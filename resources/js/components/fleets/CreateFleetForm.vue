<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';

import AppButton from '@/components/ui/AppButton.vue';
import AppCheckbox from '@/components/ui/AppCheckbox.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppTextarea from '@/components/ui/AppTextarea.vue';
import FormField from '@/components/ui/FormField.vue';
import { ApiError } from '@/services/apiClient';
import { createFleet, updateFleet } from '@/services/fleetService';
import type {
    CreateFleetPayload,
    UpdateFleetPayload,
} from '@/services/fleetService';
import type { Fleet } from '@/types/fleet';

const props = defineProps<{
    fleet?: Fleet;
}>();

const emit = defineEmits<{
    saved: [fleet: Fleet];
    cancel: [];
}>();

const editing = computed(() => props.fleet !== undefined);

const form = reactive({
    name: props.fleet?.name ?? '',
    code: props.fleet?.code ?? '',
    email: props.fleet?.email ?? '',
    phone: props.fleet?.phone ?? '',
    address: props.fleet?.address ?? '',
    latitude:
        props.fleet?.latitude === null || props.fleet?.latitude === undefined
            ? ''
            : String(props.fleet.latitude),
    longitude:
        props.fleet?.longitude === null || props.fleet?.longitude === undefined
            ? ''
            : String(props.fleet.longitude),
    timezone: props.fleet?.timezone ?? '',
    description: props.fleet?.description ?? '',
    is_active: props.fleet?.is_active ?? true,
});

type FormFieldName = keyof typeof form;

const submitting = ref(false);
const error = ref<string | null>(null);
const validationErrors = ref<Record<string, string[]>>({});

function optionalString(value: string): string | null {
    const trimmedValue = value.trim();

    return trimmedValue === '' ? null : trimmedValue;
}

function optionalNumber(value: string): number | null {
    const trimmedValue = value.trim();

    return trimmedValue === '' ? null : Number(trimmedValue);
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
    submitting.value = true;
    error.value = null;
    validationErrors.value = {};

    const payload: CreateFleetPayload | UpdateFleetPayload = {
        name: form.name.trim(),
        code: form.code.trim(),
        email: optionalString(form.email),
        phone: optionalString(form.phone),
        address: optionalString(form.address),
        latitude: optionalNumber(form.latitude),
        longitude: optionalNumber(form.longitude),
        timezone: optionalString(form.timezone),
        description: optionalString(form.description),
        is_active: form.is_active,
    };

    try {
        const fleet = props.fleet
            ? await updateFleet(props.fleet, payload)
            : await createFleet(payload);

        emit('saved', fleet);
    } catch (exception) {
        if (exception instanceof ApiError) {
            validationErrors.value = exception.errors;

            if (Object.keys(exception.errors).length === 0) {
                error.value = exception.message;
            }

            return;
        }

        error.value = editing.value
            ? 'Unable to update fleet.'
            : 'Unable to create fleet.';
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
                label="Fleet name"
                input-id="fleet-name"
                :error="fieldError('name')"
                required
            >
                <AppInput
                    id="fleet-name"
                    v-model="form.name"
                    autocomplete="organization"
                    placeholder="Main Fleet"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Code"
                input-id="fleet-code"
                :error="fieldError('code')"
                description="A short identifier for this fleet."
                required
            >
                <AppInput
                    id="fleet-code"
                    v-model="form.code"
                    maxlength="50"
                    placeholder="MAIN"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Email"
                input-id="fleet-email"
                :error="fieldError('email')"
            >
                <AppInput
                    id="fleet-email"
                    v-model="form.email"
                    type="email"
                    autocomplete="email"
                    placeholder="fleet@example.com"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Phone"
                input-id="fleet-phone"
                :error="fieldError('phone')"
            >
                <AppInput
                    id="fleet-phone"
                    v-model="form.phone"
                    type="tel"
                    autocomplete="tel"
                    placeholder="+385 1 234 5678"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                class="md:col-span-2"
                label="Address"
                input-id="fleet-address"
                :error="fieldError('address')"
            >
                <AppInput
                    id="fleet-address"
                    v-model="form.address"
                    autocomplete="street-address"
                    placeholder="Fleet address"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Latitude"
                input-id="fleet-latitude"
                :error="fieldError('latitude')"
            >
                <AppInput
                    id="fleet-latitude"
                    v-model="form.latitude"
                    type="number"
                    min="-90"
                    max="90"
                    step="any"
                    placeholder="45.8150"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Longitude"
                input-id="fleet-longitude"
                :error="fieldError('longitude')"
            >
                <AppInput
                    id="fleet-longitude"
                    v-model="form.longitude"
                    type="number"
                    min="-180"
                    max="180"
                    step="any"
                    placeholder="15.9819"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                class="md:col-span-2"
                label="Timezone"
                input-id="fleet-timezone"
                :error="fieldError('timezone')"
                description="Use an IANA timezone such as Europe/Zagreb."
            >
                <AppInput
                    id="fleet-timezone"
                    v-model="form.timezone"
                    placeholder="Europe/Zagreb"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                class="md:col-span-2"
                label="Description"
                input-id="fleet-description"
                :error="fieldError('description')"
            >
                <AppTextarea
                    id="fleet-description"
                    v-model="form.description"
                    placeholder="Optional fleet description"
                    :disabled="submitting"
                />
            </FormField>
        </div>

        <div>
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
                        Active fleet
                    </span>

                    <span class="mt-0.5 block text-xs text-muted">
                        Active fleets are available for normal fleet operations.
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

        <div class="flex justify-end gap-3 border-t border-border-default pt-5">
            <AppButton
                variant="secondary"
                :disabled="submitting"
                @click="emit('cancel')"
            >
                Cancel
            </AppButton>

            <AppButton type="submit" :loading="submitting">
                {{ editing ? 'Save changes' : 'Create fleet' }}
            </AppButton>
        </div>
    </form>
</template>
