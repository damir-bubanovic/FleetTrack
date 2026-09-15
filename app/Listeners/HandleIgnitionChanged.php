<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Alert\CreateIgnitionAlert;
use App\Events\IgnitionChanged;
use Illuminate\Contracts\Queue\ShouldQueue;

final class HandleIgnitionChanged implements ShouldQueue
{
    public function __construct(
        private readonly CreateIgnitionAlert $createIgnitionAlert,
    ) {}

    public function handle(IgnitionChanged $event): void
    {
        $this->createIgnitionAlert->execute($event);
    }
}
