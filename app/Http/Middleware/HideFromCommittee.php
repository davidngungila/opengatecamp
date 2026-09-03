<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HideFromCommittee
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isCommitteeMember()) {
            abort(403, 'Committee members cannot access this area.');
        }

        return $next($request);
    }
}
