export type Geofence = {
    id: number;
    company_id: number;
    traccar_geofence_id: number | null;
    name: string;
    description: string | null;
    area: string;
    is_active: boolean;
    last_sync_at: string | null;
    created_at: string | null;
    updated_at: string | null;
};

export type GeofenceFormData = {
    company_id?: number;
    name: string;
    description: string;
    area: string;
    is_active: boolean;
};
