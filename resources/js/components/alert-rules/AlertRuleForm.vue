<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';

import AppButton from '@/components/ui/AppButton.vue';
import AppInput from '@/components/ui/AppInput.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import FormField from '@/components/ui/FormField.vue';
import { createAlertRule, updateAlertRule } from '@/services/alertRuleService';
import { ApiError } from '@/services/apiClient';
import { authState } from '@/services/authState';
import { getCompanies } from '@/services/companyService';
import { getVehicles } from '@/services/vehicleService';
import type {
    AlertRule,
    AlertRuleFormData,
    AlertRuleSeverity,
    AlertRuleType,
} from '@/types/alertRule';
import type { Company } from '@/types/company';
import type { Vehicle } from '@/types/vehicle';

const props = defineProps<{
    alertRule?: AlertRule;
}>();

const emit = defineEmits<{
    saved: [alertRule: AlertRule];
    cancel: [];
}>();

const editing = computed(() => props.alertRule !== undefined);

const isSuperAdmin = computed(
    () => authState.user.value?.roles.includes('super_admin') ?? false,
);

const companies = ref<Company[]>([]);
const vehicles = ref<Vehicle[]>([]);
const companiesLoading = ref(false);
const vehiclesLoading = ref(false);

const companyOptions = computed(() =>
    companies.value.map((company) => ({
        value: company.id,
        label: company.name,
    })),
);

const vehicleOptions = computed(() => [
    {
        value: '',
        label: 'All vehicles',
    },
    ...vehicles.value.map((vehicle) => ({
        value: vehicle.id,
        label: vehicle.registration_number,
    })),
]);

const typeOptions: { value: AlertRuleType; label: string }[] = [
    {
        value: 'overspeed',
        label: 'Overspeed',
    },
    {
        value: 'geofence_enter',
        label: 'Geofence enter',
    },
    {
        value: 'geofence_exit',
        label: 'Geofence exit',
    },
    {
        value: 'ignition_on',
        label: 'Ignition on',
    },
    {
        value: 'ignition_off',
        label: 'Ignition off',
    },
    {
        value: 'device_offline',
        label: 'Device offline',
    },
];

const severityOptions: { value: AlertRuleSeverity; label: string }[] = [
    {
        value: 'info',
        label: 'Info',
    },
    {
        value: 'warning',
        label: 'Warning',
    },
    {
        value: 'critical',
        label: 'Critical',
    },
];

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
    company_id: props.alertRule?.company_id ?? null,
    vehicle_id: props.alertRule?.vehicle_id ?? null,
    name: props.alertRule?.name ?? '',
    type: (props.alertRule?.type ?? 'overspeed') as AlertRuleType,
    severity: (props.alertRule?.severity ?? 'warning') as AlertRuleSeverity,
    speed_limit_kmh:
        props.alertRule?.conditions.speed_limit_kmh?.toString() ?? '',
    status: props.alertRule?.is_active === false ? 'inactive' : 'active',
});

type FormFieldName = keyof typeof form;

const submitting = ref(false);
const error = ref<string | null>(null);
const validationErrors = ref<Record<string, string[]>>({});

function fieldError(field: FormFieldName | string): string | null {
    return validationErrors.value[field]?.[0] ?? null;
}

function clearFieldError(field: string): void {
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

            if (field === 'speed_limit_kmh') {
                clearFieldError('conditions.speed_limit_kmh');
            }
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

async function loadVehicles(): Promise<void> {
    vehiclesLoading.value = true;

    try {
        const response = await getVehicles(1, 100);

        vehicles.value =
            isSuperAdmin.value && form.company_id !== null
                ? response.data.filter(
                      (vehicle) =>
                          vehicle.company_id === Number(form.company_id),
                  )
                : response.data;
    } catch (exception) {
        error.value =
            exception instanceof Error
                ? exception.message
                : 'Unable to load vehicles.';
    } finally {
        vehiclesLoading.value = false;
    }
}

watch(
    () => form.company_id,
    async (companyId, previousCompanyId) => {
        if (!isSuperAdmin.value || companyId === previousCompanyId) {
            return;
        }

        if (!editing.value) {
            form.vehicle_id = null;
        }

        await loadVehicles();
    },
);

watch(
    () => form.type,
    (type) => {
        if (type !== 'overspeed') {
            form.speed_limit_kmh = '';
            clearFieldError('conditions.speed_limit_kmh');
        }
    },
);

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

    const payload: AlertRuleFormData = {
        vehicle_id: form.vehicle_id === null ? null : Number(form.vehicle_id),
        name: form.name.trim(),
        type: form.type,
        severity: form.severity,
        conditions:
            form.type === 'overspeed'
                ? {
                      speed_limit_kmh: Number(form.speed_limit_kmh),
                  }
                : {},
        is_active: form.status === 'active',
    };

    if (isSuperAdmin.value && form.company_id !== null) {
        payload.company_id = Number(form.company_id);
    }

    try {
        const alertRule = props.alertRule
            ? await updateAlertRule(props.alertRule, payload)
            : await createAlertRule(payload);

        emit('saved', alertRule);
    } catch (exception) {
        if (exception instanceof ApiError) {
            validationErrors.value = exception.errors;

            if (Object.keys(exception.errors).length === 0) {
                error.value = exception.message;
            }

            return;
        }

        error.value = editing.value
            ? 'Unable to update alert rule.'
            : 'Unable to create alert rule.';
    } finally {
        submitting.value = false;
    }
}

onMounted(async () => {
    await loadCompanies();
    await loadVehicles();
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
                input-id="alert-rule-company"
                :error="fieldError('company_id')"
                required
            >
                <AppSelect
                    id="alert-rule-company"
                    v-model="form.company_id"
                    :options="companyOptions"
                    placeholder="Select company"
                    required
                    :disabled="submitting || companiesLoading"
                />
            </FormField>

            <FormField
                label="Name"
                input-id="alert-rule-name"
                :error="fieldError('name')"
                required
            >
                <AppInput
                    id="alert-rule-name"
                    v-model="form.name"
                    placeholder="High speed warning"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Type"
                input-id="alert-rule-type"
                :error="fieldError('type')"
                required
            >
                <AppSelect
                    id="alert-rule-type"
                    v-model="form.type"
                    :options="typeOptions"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Severity"
                input-id="alert-rule-severity"
                :error="fieldError('severity')"
                required
            >
                <AppSelect
                    id="alert-rule-severity"
                    v-model="form.severity"
                    :options="severityOptions"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                label="Vehicle"
                input-id="alert-rule-vehicle"
                :error="fieldError('vehicle_id')"
            >
                <AppSelect
                    id="alert-rule-vehicle"
                    v-model="form.vehicle_id"
                    :options="vehicleOptions"
                    :disabled="submitting || vehiclesLoading"
                />
            </FormField>

            <FormField
                label="Status"
                input-id="alert-rule-status"
                :error="fieldError('status')"
                required
            >
                <AppSelect
                    id="alert-rule-status"
                    v-model="form.status"
                    :options="statusOptions"
                    required
                    :disabled="submitting"
                />
            </FormField>

            <FormField
                v-if="form.type === 'overspeed'"
                label="Speed limit (km/h)"
                input-id="alert-rule-speed-limit"
                :error="fieldError('conditions.speed_limit_kmh')"
                required
            >
                <AppInput
                    id="alert-rule-speed-limit"
                    v-model="form.speed_limit_kmh"
                    type="number"
                    min="1"
                    step="1"
                    placeholder="90"
                    required
                    :disabled="submitting"
                />
            </FormField>
        </div>

        <p class="text-sm text-muted">
            Leave the vehicle set to All vehicles to apply this rule across the
            company fleet.
        </p>

        <div class="flex justify-end gap-3">
            <AppButton
                type="button"
                variant="secondary"
                :disabled="submitting"
                @click="emit('cancel')"
            >
                Cancel
            </AppButton>

            <AppButton type="submit" :loading="submitting">
                {{ editing ? 'Save changes' : 'Create rule' }}
            </AppButton>
        </div>
    </form>
</template>
