<?php

namespace App\Actions\Auth;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class LogoutUser
{
    /**
     * Revoke the current API token.
     */
    public function handle(User $user): void
    {
        /** @var PersonalAccessToken $token */
        $token = $user->currentAccessToken();

        $token->delete();
    }
}