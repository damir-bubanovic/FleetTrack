<?php

namespace App\Actions\Auth;

use App\Models\User;

class LogoutUser
{
    /**
     * Revoke the current API token.
     */
    public function handle(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}