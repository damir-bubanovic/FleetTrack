<?php

declare(strict_types=1);

namespace App\Actions\Alert;

use App\Models\Alert;
use App\Models\User;

final class AcknowledgeAlert
{
    public function execute(Alert $alert, User $user): Alert
    {
        if ($alert->acknowledged_at !== null) {
            return $alert;
        }

        $alert->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);

        return $alert->refresh();
    }
}
