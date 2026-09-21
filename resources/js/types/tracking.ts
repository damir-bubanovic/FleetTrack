export type TrackingDevice = {
    id: number | null;
    name: string | null;
    unique_id: string | null;
    traccar_device_id: number | null;
};

export type TrackingVehicle = {
    id: number;
    name: string;
};

export type TrackingStatus = {
    online: boolean;
    last_seen_at: string | null;
};

export type TrackingPosition = {
    id: number | null;
    device_id: number | null;
    latitude: number | null;
    longitude: number | null;
    altitude: number | null;
    speed: number | null;
    course: number | null;
    accuracy: number | null;
    fix_time: string | null;
    server_time: string | null;
    attributes: Record<string, unknown>;
};

export type LivePosition = {
    device: TrackingDevice;
    vehicle: TrackingVehicle | null;
    status: TrackingStatus;
    position: TrackingPosition;
};

export type LivePositionsResponse = {
    data: LivePosition[];
};
