<?php

return [

    /*
    |--------------------------------------------------------------------------
    | System Company
    |--------------------------------------------------------------------------
    |
    | FleetTrack uses an internal system company to scope global roles when
    | using Spatie Permission Teams. This company should never appear in the
    | normal Companies module.
    |
    */

    'system_company_slug' => env(
        'FLEETTRACK_SYSTEM_COMPANY_SLUG',
        'fleettrack-logistics'
    ),

];