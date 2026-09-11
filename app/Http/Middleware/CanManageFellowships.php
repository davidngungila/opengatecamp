<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CanManageFellowships
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $allowed = ['Super Administrator', 'Chairperson', 'Secretary', 'Treasurer'];

        if (! $user || ! in_array($user->role?->name, $allowed, true)) {
            abort(403, 'Only administrators, secretary or treasurer can access fellowships.');
        }

        return $next($request);
    }
}
