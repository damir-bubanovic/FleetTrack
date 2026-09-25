export type ReportDateRange = {
    from: string;
    to: string;
};

export type VehicleTrip = {
    device_id: number | null;
    driver_id: string | null;
    started_at: string | null;
    ended_at: string | null;
    start_latitude: number | null;
    start_longitude: number | null;
    end_latitude: number | null;
    end_longitude: number | null;
    distance_km: number | null;
    duration_seconds: number | null;
    average_speed: number | null;
    max_speed: number | null;
    speed_unit: 'knots';
    start_address: string | null;
    end_address: string | null;
};

export type VehicleTripSummary = {
    position_count: number;
    started_at: string | null;
    ended_at: string | null;
    duration_seconds: number;
    distance_km: number;
    average_speed: number;
    max_speed: number;
    moving_seconds: number;
    stopped_seconds: number;
    speed_unit: 'knots';
};

export type VehicleStop = {
    device_id: number | null;
    address: string | null;
    latitude: number | null;
    longitude: number | null;
    start_time: string | null;
    end_time: string | null;
    duration: number | null;
    engine_hours: number | null;
};

export type VehicleEvent = {
    id: number | null;
    device_id: number | null;
    type: string | null;
    event_time: string | null;
    position_id: number | null;
    geofence_id: number | null;
    maintenance_id: number | null;
    attributes: Record<string, unknown>;
};

export type VehicleRoutePosition = {
    id: number | null;
    device_id: number | null;
    protocol: string | null;
    device_time: string | null;
    fix_time: string | null;
    server_time: string | null;
    latitude: number | null;
    longitude: number | null;
    altitude: number | null;
    speed: number | null;
    course: number | null;
    accuracy: number | null;
    address: string | null;
    attributes: Record<string, unknown>;
};

export type VehicleSummary = {
    device_id: number | null;
    device_name: string | null;
    distance: number | null;
    average_speed: number | null;
    max_speed: number | null;
    spent_fuel: number | null;
    start_odometer: number | null;
    end_odometer: number | null;
};

export type VehicleHours = {
    device_id: number | null;
    device_name: string | null;
    hours: number | null;
};

export type VehicleCombinedReport = Record<string, unknown>;
