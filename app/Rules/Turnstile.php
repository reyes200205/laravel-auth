<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Omitir si la validación está desactivada en la configuración (e.g. en tests)
        if (!config('services.turnstile.enabled', true)) {
            return;
        }

        $secret = config('services.turnstile.secret_key');

        if (blank($value)) {
            $fail('The captcha validation is required.');
            return;
        }

        $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
            'secret' => $secret,
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (!$response->successful() || !$response->json('success')) {
            Log::error('Turnstile validation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
                'secret' => $secret ? (substr($secret, 0, 10) . '...') : null,
                'ip' => request()->ip(),
            ]);
            $fail('The captcha verification failed. Please try again.');
        }
    }
}
