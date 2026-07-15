<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case CompanyAdmin = 'company_admin';
    case FleetManager = 'fleet_manager';
    case Driver = 'driver';

    /**
     * Return all available role values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Return a human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::CompanyAdmin => 'Company Administrator',
            self::FleetManager => 'Fleet Manager',
            self::Driver => 'Driver',
        };
    }
}
