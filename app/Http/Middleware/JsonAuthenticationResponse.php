<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class JsonAuthenticationResponse
{
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => 'token expirado'
            ], 401);
        }
    }
}
