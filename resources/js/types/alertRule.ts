export type AlertRuleType =
    | 'overspeed'
    | 'geofence_enter'
    | 'geofence_exit'
    | 'ignition_on'
    | 'ignition_off'
    | 'device_offline';

export type AlertRuleSeverity = 'info' | 'warning' | 'critical';

export type AlertRuleConditions = {
    speed_limit_kmh?: number;
};

export type AlertRule = {
    id: number;
    company_id: number;
    vehicle_id: number | null;
    name: string;
    type: AlertRuleType;
    severity: AlertRuleSeverity;
    conditions: AlertRuleConditions;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
};

export type AlertRuleFormData = {
    company_id?: number;
    vehicle_id: number | null;
    name: string;
    type: AlertRuleType;
    severity: AlertRuleSeverity;
    conditions: AlertRuleConditions;
    is_active: boolean;
};
