<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';

import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import FormField from '@/components/ui/FormField.vue';
import { ApiError } from '@/services/apiClient';
import { authState } from '@/services/authState';
import { getCompanies } from '@/services/companyService';
import { createGeofence, updateGeofence } from '@/services/geofenceService';
import type { Company } from '@/types/company';
import type { Geofence, GeofenceFormData } from '@/types/geofence';

const props = defineProps<{
    geofence?: Geofence;
}>();

const emit = defineEmits<{
    saved: [geofence: Geofence];
    cancel: [];
}>();

const editing = computed(() => props.geofence !== undefined);

const isSuperAdmin = computed(
    () => authState.user.value?.roles.includes('super_admin') ?? false,
);

const companies = ref<Company[]>([]);
const companiesLoading = ref(false);

const companyOptions = computed(() =>
    companies.value.map((company) => ({
        value: company.id,
        label: company.name,
    })),
);

const statusOptions = [
    {
        value: 'active',
        label: 'Active',
    },
    {
        value: 'inactive',
        label: 'Inactive',
    },
];

const form = reactive({
    company_id: props.geofence?.company_id ?? null,
    name: props.geofence?.name ?? '',
    description: props.geofence?.description ?? '',
    area: props.geofence?.area ?? '',
    status: props.geofence?.is_active ? 'active' : 'inactive',
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

    companiesLoading.value = true;

    try {
        const response = await getCompanies();

        companies.value = response.data;

        if (form.company_id === null && companies.value.length === 1) {
            form.company_id = companies.value[0].id;
        }
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load companies.';
    } finally {
        companiesLoading.value = false;
    }
}

async function submit(): Promise<void> {
    if (isSuperAdmin.value && form.company_id === null) {
        validationErrors.value = {
            company_id: ['The company field is required.'],
        };

        return;
    }

    submitting.value = true;
    error.value = null;
    validationErrors.value = {};

    const payload: GeofenceFormData = {
        name: form.name.trim(),
        description: form.description.trim(),
        area: form.area.trim(),
        is_active: form.status === 'active',
    };

    if (isSuperAdmin.value && form.company_id !== null) {
        payload.company_id = Number(form.company_id);
    }

    try {
        const geofence = props.geofence
            ? await updateGeofence(props.geofence, payload)
            : await createGeofence(payload);

        emit('saved', geofence);
    } catch (exception) {
        if (exception instanceof ApiError) {
            validationErrors.value = exception.errors;

            if (Object.keys(exception.errors).length === 0) {
                error.value = exception.message;
            }

            return;
        }

        error.value = editing.value
            ? 'Unable to update geofence.'
            : 'Unable to create geofence.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    await loadCompanies();
});
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
                input-id="geofence-company"
                :error="fieldError('company_id')"
                required
            >
                <AppSelect
                    id="geofence-company"
                    v-model="form.company_id"
                    :options="companyOptions"
                    placeholder="Select company"
                    required
                    :disabled="submitting || companiesLoading"
                />
            </FormField>

            <FormField
                label="Name"
                input-id="geofence-name"
                :error="fieldError('name')"
                required
            >
                <AppInput
                    id="geofence-name"
                    v-model="form.name"
                    placeholder="Zagreb Depot"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Description"
                input-id="geofence-description"
                :error="fieldError('description')"
            >
                <AppInput
                    id="geofence-description"
                    v-model="form.description"
                    placeholder="Main depot geofence"
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Status"
                input-id="geofence-status"
                :error="fieldError('status')"
                required
            >
                <AppSelect
                    id="geofence-status"
                    v-model="form.status"
                    :options="statusOptions"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <div class="md:col-span-2">
                <FormField
                    label="Area"
                    input-id="geofence-area"
                    :error="fieldError('area')"
                    required
                >
                    <AppInput
                        id="geofence-area"
                        v-model="form.area"
                        placeholder="CIRCLE (45.8150 15.9819, 500)"
                        required
                        :disabled="submitting"
                    />
                </FormField>

                <p class="mt-2 text-xs text-muted">
                    Enter the geofence area using Traccar WKT syntax.
                </p>
            </div>
        </div>

        <div
            v-if="editing"
            class="rounded-lg border border-border-default bg-surface-muted px-4 py-3"
        >
            <div class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium text-muted">
                        Traccar geofence ID
                    </p>

                    <p class="mt-1 font-medium text-content">
                        {{
                            geofence?.traccar_geofence_id ?? 'Not synchronized'
                        }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-muted">
                        Last synchronized
                    </p>

                    <p class="mt-1 font-medium text-content">
                        {{ geofence?.last_sync_at ?? 'Never' }}
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

            <AppButton
                type="submit"
                :loading="submitting"
                :disabled="companiesLoading"
            >
                {{ editing ? 'Save changes' : 'Create geofence' }}
            </AppButton>
        </div>
    </form>
</template>
