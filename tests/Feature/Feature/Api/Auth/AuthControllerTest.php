<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\CreatesCompanies;

uses(
    RefreshDatabase::class,
    CreatesCompanies::class,
);

test('user can login with valid credentials', function (): void {

    $company = $this->createCompany();

    $user = User::factory()->create([
        'company_id' => $company->id,
        'email' => 'admin@example.com',
        'password' => 'password123',
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'password123',
        'device_name' => 'Postman',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', 'admin@example.com')
        ->assertJsonStructure([
            'token',
            'token_type',
            'user',
        ]);

    expect($user->fresh()->last_login_at)->not->toBeNull();

    expect($user->fresh()->tokens()->count())->toBe(1);
});

test('user cannot login with invalid password', function (): void {

    $company = $this->createCompany();

    User::factory()->create([
        'company_id' => $company->id,
        'email' => 'admin@example.com',
        'password' => 'password123',
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnauthorized();
});

test('inactive user cannot login', function (): void {

    $company = $this->createCompany();

    User::factory()->create([
        'company_id' => $company->id,
        'email' => 'admin@example.com',
        'password' => 'password123',
        'is_active' => false,
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'admin@example.com',
        'password' => 'password123',
    ])
        ->assertUnauthorized();
});

test('authenticated user can retrieve own profile', function (): void {

    $company = $this->createCompany();

    $user = User::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

test('authenticated user can logout', function (): void {

    $company = $this->createCompany();

    $user = User::factory()->create([
        'company_id' => $company->id,
        'is_active' => true,
    ]);

    $token = $user->createToken('Postman')->plainTextToken;

    expect($user->tokens()->count())->toBe(1);

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJson([
            'message' => 'Logged out successfully.',
        ]);

    expect($user->fresh()->tokens()->count())->toBe(0);
});
