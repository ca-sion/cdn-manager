<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Edition
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $editionId = $request->session()->get('edition_id');

        if (! $editionId) {
            $editionId = setting('edition_id', config('cdn.default_edition_id'));
        }

        $request->session()->put('edition_id', $editionId);

        return $next($request);
    }
}
