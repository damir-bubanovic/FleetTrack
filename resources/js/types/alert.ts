export type AlertType =
    | 'overspeed'
    | 'geofence_enter'
    | 'geofence_exit'
    | 'ignition_on'
    | 'ignition_off'
    | 'device_offline';

export type AlertSeverity = 'info' | 'warning' | 'critical';

export type Alert = {
    id: number;
    company_id: number;
    vehicle_id: number | null;
    device_id: number | null;
    geofence_id: number | null;
    type: AlertType;
    severity: AlertSeverity;
    title: string;
    message: string;
    occurred_at: string;
    acknowledged_at: string | null;
    acknowledged_by: number | null;
    created_at: string | null;
    updated_at: string | null;
};
