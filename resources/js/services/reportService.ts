import {
    combined,
    events,
    hours,
    route,
    stops,
    summary,
    trips,
    tripSummary,
} from '@/routes/reports/vehicles';
import { apiRequest } from '@/services/apiClient';
import type {
    ReportDateRange,
    VehicleCombinedReport,
    VehicleEvent,
    VehicleHours,
    VehicleRoutePosition,
    VehicleStop,
    VehicleSummary,
    VehicleTrip,
    VehicleTripSummary,
} from '@/types/report';
import type { Vehicle } from '@/types/vehicle';

function vehicleId(vehicle: Vehicle | number): number {
    return typeof vehicle === 'number' ? vehicle : vehicle.id;
}

function query(range: ReportDateRange) {
    return {
        from: range.from,
        to: range.to,
    };
}

export function getVehicleTrips(
    vehicle: Vehicle | number,
    range: ReportDateRange,
): Promise<VehicleTrip[]> {
    return apiRequest<VehicleTrip[]>(
        trips.url(vehicleId(vehicle), {
            query: query(range),
        }),
    );
}

export function getVehicleTripSummary(
    vehicle: Vehicle | number,
    range: ReportDateRange,
): Promise<VehicleTripSummary> {
    return apiRequest<VehicleTripSummary>(
        tripSummary.url(vehicleId(vehicle), {
            query: query(range),
        }),
    );
}

export function getVehicleStops(
    vehicle: Vehicle | number,
    range: ReportDateRange,
): Promise<VehicleStop[]> {
    return apiRequest<VehicleStop[]>(
        stops.url(vehicleId(vehicle), {
            query: query(range),
        }),
    );
}

export function getVehicleEvents(
    vehicle: Vehicle | number,
    range: ReportDateRange,
): Promise<VehicleEvent[]> {
    return apiRequest<VehicleEvent[]>(
        events.url(vehicleId(vehicle), {
            query: query(range),
        }),
    );
}

export function getVehicleRoute(
    vehicle: Vehicle | number,
    range: ReportDateRange,
): Promise<VehicleRoutePosition[]> {
    return apiRequest<VehicleRoutePosition[]>(
        route.url(vehicleId(vehicle), {
            query: query(range),
        }),
    );
}

export function getVehicleSummary(
    vehicle: Vehicle | number,
    range: ReportDateRange,
): Promise<VehicleSummary[]> {
    return apiRequest<VehicleSummary[]>(
        summary.url(vehicleId(vehicle), {
            query: query(range),
        }),
    );
}

export function getVehicleHours(
    vehicle: Vehicle | number,
    range: ReportDateRange,
): Promise<VehicleHours[]> {
    return apiRequest<VehicleHours[]>(
        hours.url(vehicleId(vehicle), {
            query: query(range),
        }),
    );
}

export function getVehicleCombinedReport(
    vehicle: Vehicle | number,
    range: ReportDateRange,
): Promise<VehicleCombinedReport[]> {
    return apiRequest<VehicleCombinedReport[]>(
        combined.url(vehicleId(vehicle), {
            query: query(range),
        }),
    );
}
