<?php

namespace App\Http\Middleware;

use App\Models\Screen;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For the play pages: a public screen is open to anyone with the link, a private one
 * sends guests to login. Runs as route middleware (after route-model binding) rather
 * than inside the Livewire component, where Laravel's unauthenticated redirect breaks.
 */
class AuthenticateUnlessPublicScreen
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $screen = $request->route('screen');

        if ($screen instanceof Screen && ! $screen->is_public && ! $request->user()) {
            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
