<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CommitteeReadonly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->hasRole('committee')
            && in_array($request->method(), ['PUT', 'PATCH', 'DELETE'], true)
            && ! $request->is('account/*')
            && ! $request->is('portal/*')
        ) {
            abort(403, 'Read-only role: editing or deleting records is not permitted.');
        }

        return $next($request);
    }
}
