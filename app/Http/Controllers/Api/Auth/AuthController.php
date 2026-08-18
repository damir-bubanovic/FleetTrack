<?php

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\LogoutUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly LoginUser $loginUser,
        private readonly LogoutUser $logoutUser,
    ) {}

    /**
     * Authenticate a user and issue a Sanctum token.
     */
    public function login(
        LoginRequest $request,
    ): JsonResponse {
        $result = $this->loginUser->handle(
            $request->validated(),
        );

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => UserResource::make($result['user']),
        ]);
    }

    /**
     * Revoke the current Sanctum token.
     */
    public function logout(
        Request $request,
    ): JsonResponse {
        $this->logoutUser->handle(
            $request->user(),
        );

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return the authenticated user.
     */
    public function me(
        Request $request,
    ): UserResource {
        return UserResource::make(
            $request->user(),
        );
    }
}
