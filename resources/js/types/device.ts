export type DeviceStatus = 'active' | 'inactive' | 'offline';

export type Device = {
    id: number;
    company_id: number;
    vehicle_id: number | null;
    traccar_device_id: number | null;
    name: string;
    unique_id: string;
    status: DeviceStatus;
    last_sync_at: string | null;
    created_at: string;
    updated_at: string;
};
