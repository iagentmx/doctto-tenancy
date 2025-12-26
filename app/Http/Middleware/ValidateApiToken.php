<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Api-Token');
        $validToken = env('APP_API_TOKEN');

        // Validar apiToken
        if ($token !== $validToken) {
            return response()->json([
                'message' => 'Invalid API Token',
            ], 401);
        }

        return $next($request);
    }
}
