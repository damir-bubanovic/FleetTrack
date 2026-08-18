<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class LoginUser
{
    /**
     * Authenticate a user and create a Sanctum token.
     *
     * @param  array<string, mixed>  $credentials
     * @return array{token: string, user: User}
     *
     * @throws AuthenticationException
     */
    public function handle(array $credentials): array
    {
        /** @var User|null $user */
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (
            $user === null
            || ! Hash::check($credentials['password'], $user->password)
        ) {
            throw new AuthenticationException(
                'The provided credentials are incorrect.'
            );
        }

        if (! $user->is_active) {
            throw new AuthenticationException(
                'Your account has been deactivated.'
            );
        }

        $user->update([
            'last_login_at' => now(),
        ]);

        $token = $user->createToken(
            $credentials['device_name'] ?? 'FleetTrack API',
            ['*'],
        )->plainTextToken;

        return [
            'token' => $token,
            'user' => $user->fresh(),
        ];
    }
}
