<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Alert\CreateOverspeedAlert;
use App\Events\OverspeedOccurred;
use Illuminate\Contracts\Queue\ShouldQueue;

final class HandleOverspeedOccurred implements ShouldQueue
{
    public function __construct(
        private readonly CreateOverspeedAlert $createOverspeedAlert,
    ) {}

    public function handle(OverspeedOccurred $event): void
    {
        $this->createOverspeedAlert->execute($event);
    }
}
