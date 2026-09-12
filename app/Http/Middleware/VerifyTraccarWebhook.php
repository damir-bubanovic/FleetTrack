<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyTraccarWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('services.traccar.webhook_token');

        if (! is_string($expectedToken) || $expectedToken === '') {
            abort(500, 'Traccar webhook token is not configured.');
        }

        $providedToken = $request->bearerToken();

        if (
            ! is_string($providedToken)
            || ! hash_equals($expectedToken, $providedToken)
        ) {
            abort(401, 'Invalid Traccar webhook token.');
        }

        return $next($request);
    }
}
