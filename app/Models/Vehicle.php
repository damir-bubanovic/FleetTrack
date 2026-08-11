<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $fleet_id
 * @property string $registration_number
 * @property string $vin
 * @property string $manufacturer
 * @property string $model
 * @property int $year
 * @property string|null $color
 * @property string $fuel_type
 * @property string $transmission
 * @property int $odometer
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read Fleet $fleet
 * @property-read Device|null $device
 *
 * @use HasFactory<VehicleFactory>
 */
#[Fillable([
    'company_id',
    'fleet_id',
    'registration_number',
    'vin',
    'manufacturer',
    'model',
    'year',
    'color',
    'fuel_type',
    'transmission',
    'odometer',
    'notes',
    'is_active',
])]
class Vehicle extends Model
{
    use BelongsToCompany;
    use HasFactory;
    use SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'odometer' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the fleet that owns the vehicle.
     *
     * @return BelongsTo<Fleet, $this>
     */
    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    /**
     * Get the GPS device assigned to the vehicle.
     *
     * @return HasOne<Device, $this>
     */
    public function device(): HasOne
    {
        return $this->hasOne(Device::class);
    }

    /**
     * Get the vehicle display name.
     */
    public function displayName(): string
    {
        return "{$this->manufacturer} {$this->model}";
    }
}