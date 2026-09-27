<?php

declare(strict_types=1);

use App\Actions\Geofence\DeleteGeofence;
use App\Events\GeofenceDeleted;
use App\Models\Geofence;
use Illuminate\Support\Facades\Event;

it('dispatches the deleted event after the geofence is deleted', function (): void {
    Event::listen(
        GeofenceDeleted::class,
        function (GeofenceDeleted $event): void {
            expect(
                Geofence::query()
                    ->whereKey($event->geofence->id)
                    ->exists(),
            )->toBeFalse();
        },
    );

    $geofence = Geofence::factory()->create();

    app(DeleteGeofence::class)->handle($geofence);

    $this->assertDatabaseMissing('geofences', [
        'id' => $geofence->id,
    ]);
});
