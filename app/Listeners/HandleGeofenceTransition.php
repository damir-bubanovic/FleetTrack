<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GeofenceTransitionOccurred;
use Illuminate\Contracts\Queue\ShouldQueue;

final class HandleGeofenceTransition implements ShouldQueue
{
    public function handle(GeofenceTransitionOccurred $event): void
    {
        //
    }
}
