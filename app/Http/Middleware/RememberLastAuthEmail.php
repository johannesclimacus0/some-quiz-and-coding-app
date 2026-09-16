<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RememberLastAuthEmail
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('login.store', 'register.store')) {
            $email = $request->input('email');

            if (is_string($email) && trim($email) !== '') {
                $request->session()->put('auth.last_email', trim($email));
            }
        }

        return $next($request);
    }
}
