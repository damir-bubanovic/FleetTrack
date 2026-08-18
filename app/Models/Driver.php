<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $fleet_id
 * @property int|null $user_id
 * @property string $employee_number
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property string|null $email
 * @property string $license_number
 * @property string $license_category
 * @property Carbon $license_expiry_date
 * @property Carbon $employment_date
 * @property string|null $notes
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Company $company
 * @property-read Fleet $fleet
 * @property-read User|null $user
 *
 * @use HasFactory<DriverFactory>
 */
#[Fillable([
    'company_id',
    'fleet_id',
    'user_id',
    'employee_number',
    'first_name',
    'last_name',
    'phone',
    'email',
    'license_number',
    'license_category',
    'license_expiry_date',
    'employment_date',
    'notes',
    'is_active',
])]
class Driver extends Model
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
            'license_expiry_date' => 'date',
            'employment_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the fleet that the driver belongs to.
     *
     * @return BelongsTo<Fleet, $this>
     */
    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    /**
     * Get the optional user account linked to the driver.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the driver's full name.
     */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Determine whether the driver's licence has expired.
     */
    public function hasExpiredLicense(): bool
    {
        return $this->license_expiry_date->isPast();
    }
}
