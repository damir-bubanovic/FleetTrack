<?php

namespace App\Actions\Driver;

use App\Models\Driver;
use Illuminate\Support\Facades\DB;

class DeleteDriver
{
    /**
     * Delete the driver.
     */
    public function handle(Driver $driver): void
    {
        DB::transaction(function () use ($driver): void {
            $driver->delete();
        });
    }
}