<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\GeofenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @use HasFactory<GeofenceFactory>
 */
class Geofence extends Model
{
    use BelongsToCompany;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'traccar_geofence_id',
        'name',
        'description',
        'area',
        'is_active',
        'last_sync_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'traccar_geofence_id' => 'integer',
            'is_active' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }
}
