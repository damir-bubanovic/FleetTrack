<?php

declare(strict_types=1);

namespace App\Actions\AlertRule;

use App\Models\AlertRule;
use Illuminate\Support\Facades\DB;

final class DeleteAlertRule
{
    public function execute(AlertRule $alertRule): void
    {
        DB::transaction(
            fn () => $alertRule->delete(),
        );
    }
}
