<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesCompanies;
use Tests\Traits\CreatesFleets;
use Tests\Traits\CreatesUsers;

uses(
    RefreshDatabase::class,
    CreatesUsers::class,
    CreatesCompanies::class,
    CreatesFleets::class,
);

test('super admin can list fleets', function (): void {

    $companyA = $this->createCompany([
        'name' => 'Company A',
        'slug' => 'company-a',
    ]);

    $companyB = $this->createCompany([
        'name' => 'Company B',
        'slug' => 'company-b',
    ]);

    $this->createFleets($companyA, 3);
    $this->createFleets($companyB, 2);

    $user = $this->actingAsSuperAdmin();

    $response = $this->getJson('/api/v1/fleets');

    $response
        ->assertOk()
        ->assertJsonCount(5, 'data');
});