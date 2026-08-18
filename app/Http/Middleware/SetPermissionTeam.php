<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPermissionTeam
{
    /**
     * Handle an incoming request.
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $user = $request->user();

        if ($user !== null) {
            setPermissionsTeamId($user->company_id);

            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');
        }

        return $next($request);
    }
}
