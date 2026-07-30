<?php

namespace App\Actions\Fleet;

use App\Models\Fleet;
use Illuminate\Support\Facades\DB;

class DeleteFleet
{
    /**
     * Delete a fleet.
     */
    public function handle(Fleet $fleet): void
    {
        DB::transaction(function () use ($fleet): void {
            $fleet->delete();
        });
    }
}