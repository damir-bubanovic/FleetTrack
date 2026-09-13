<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Alert\CreateGeofenceAlert;
use App\Events\GeofenceTransitionOccurred;
use Illuminate\Contracts\Queue\ShouldQueue;

final class HandleGeofenceTransition implements ShouldQueue
{
    public function __construct(
        private readonly CreateGeofenceAlert $createGeofenceAlert,
    ) {}

    public function handle(GeofenceTransitionOccurred $event): void
    {
        $this->createGeofenceAlert->execute($event);
    }
}
