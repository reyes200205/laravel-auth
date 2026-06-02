<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CustomRateLimiter
{
    public function handle(Request $request, Closure $next, string $keyName, $maxAttempts = 5, $decayMinutes = 1): Response
    {
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        $ip = $request->ip();
        $throttleKey = $keyName . '|' . $ip;

        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            Log::channel('session')->alert('Too many requests', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'key_name' => $keyName,
                'max_attempts' => $maxAttempts,
                'decay_minutes' => $decayMinutes,
                'throttle_key' => $throttleKey,
                'seconds' => $seconds,
            ]);

            // Si es una petición de Inertia
            if ($request->hasHeader('X-Inertia')) {
                return Inertia::render('Errors/TooManyRequests', [
                    'seconds' => $seconds,
                ])->toResponse($request)->setStatusCode(429);
            }

            // Si es API o Axios que no sea Inertia y espera JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Too many requests. Please try again in {$seconds} seconds."
                ], 429);
            }

            // Respuesta para peticiones tradicionales del navegador
            abort(429, "Too many requests. Please try again in {$seconds} seconds.");
        }

        RateLimiter::hit($throttleKey, $decayMinutes * 60);

        return $next($request);
    }
}
