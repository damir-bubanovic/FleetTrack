<?php

use App\Models\Company;
use Tests\Traits\CreatesUsers;
use Tests\Traits\CreatesCompanies;

uses(CreatesUsers::class, CreatesCompanies::class);

test('super admin can list companies', function () {
    $this->createCompanies(3);

    $this->actingAsSuperAdmin();

    $response = $this->getJson('/api/v1/companies');

    $response
        ->assertOk()
        ->assertJsonCount(3, 'data');
});