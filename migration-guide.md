# Guía de Migración: Rate Limiter Personalizado y Cloudflare Turnstile en Laravel 10 + Inertia/Vue

Esta guía documenta los cambios realizados en el backend y proporciona instrucciones claras para migrar la integración de **Rate Limiting** por IP y **Cloudflare Turnstile** de un entorno Blade a un entorno **Laravel 10 + Inertia/Vue**.

---

## 1. Configuración de Entorno y Variables (`.env` y `config`)

### Variables de Entorno (`.env` y `.env.example`)
Agrega las claves de Turnstile al final del archivo. Para desarrollo local se usan las claves de prueba (auto-aprobación):
```env
TURNSTILE_SITE_KEY=1x00000000000000000000AA
TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA
```

### Configuración del Servicio (`config/services.php`)
Registra las credenciales para que Laravel las acceda con facilidad:
```php
'turnstile' => [
    'site_key' => env('TURNSTILE_SITE_KEY'),
    'secret_key' => env('TURNSTILE_SECRET_KEY'),
],
```

---

## 2. Lógica del Backend (Laravel)

### Regla de Validación de Turnstile (`app/Rules/Turnstile.php`)
Esta regla hace una petición POST a la API de Cloudflare para verificar el token recibido. Si se ejecutan pruebas unitarias (PHPUnit), la regla se salta automáticamente para no romper los tests.

```php
<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Omitir en tests unitarios
        if (app()->runningUnitTests()) {
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
            \Illuminate\Support\Facades\Log::error('Turnstile validation failed', [
                'status' => $response->status(),
                'body' => $response->json(),
                'secret' => $secret,
                'ip' => request()->ip(),
            ]);
            $fail('The captcha verification failed. Please try again.');
        }
    }
}
```

### Middleware del Rate Limiter (`app/Http/Middleware/CustomRateLimiter.php`)
Este middleware limita las peticiones por IP usando la fachada `RateLimiter` de Laravel. A diferencia del middleware `throttle` nativo, si es una petición web/Inertia redirige de vuelta con un mensaje de error limpio en el campo `email` en lugar de abortar con una página 429.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

            // Si es API o Axios/Inertia espera JSON en el bloqueo global
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Too many requests."
                ], 429);
            }

            // Redirección de vuelta con error para peticiones Inertia/Formularios Web
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors([
                    'email' => "Too many requests. Please try again in {$seconds} seconds."
                ]);
        }

        RateLimiter::hit($throttleKey, $decayMinutes * 60);

        return $next($request);
    }
}
```

### Registro de Middleware (`app/Http/Kernel.php`)
Registra el nuevo middleware en el array `$middlewareAliases`:
```php
'custom.throttle' => \App\Http\Middleware\CustomRateLimiter::class,
```

### Aplicación en Rutas (`routes/auth.php`)
Sustituye el middleware `throttle` en las rutas POST de login y registro por el nuevo middleware personalizado:
```php
Route::post('register', [RegisteredUserController::class, 'store'])
            ->middleware('custom.throttle:register,3,1'); // 3 intentos/min

Route::post('login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('custom.throttle:login,5,1');    // 5 intentos/min
```

---

## 3. Integración en Controladores y Requests de Validación

Debemos requerir y validar el token `'cf-turnstile-response'`. En pruebas unitarias se pone como `'nullable'` para que los tests pasen sin interactuar con Cloudflare.

### Validación en Registro (`app/Http/Controllers/Auth/RegisteredUserController.php`)
```php
use App\Rules\Turnstile;

$request->validate([
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
    'password' => ['required', 'confirmed', Rules\Password::defaults()],
    'cf-turnstile-response' => [app()->runningUnitTests() ? 'nullable' : 'required', new Turnstile],
]);
```

### Validación en Login (`app/Http/Requests/Auth/LoginRequest.php`)
```php
use App\Rules\Turnstile;

public function rules(): array
{
    return [
        'email' => ['required', 'string', 'email'],
        'password' => ['required', 'string'],
        'cf-turnstile-response' => [app()->runningUnitTests() ? 'nullable' : 'required', new Turnstile],
    ];
}
```

---

## 4. Adaptación a Inertia.js + Vue 3 (Instrucciones para el Agente)

Puesto que el nuevo kit de inicio usa **Inertia y Vue 3** en lugar de Blade, el otro agente deberá adaptar el frontend de la siguiente manera:

### A. Cargar el script de Turnstile
El script de Cloudflare Turnstile debe cargarse en el cliente.
- En Inertia, la página base suele ser `resources/views/app.blade.php`.
- Puede agregarse allí globalmente en el `<head>`:
  ```html
  <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
  ```
- O alternativamente, inyectarlo dinámicamente en el ciclo de vida `onMounted` del componente Vue donde se use.

### B. Implementación del Widget en Vue (Login.vue y Register.vue)
En los formularios de Vue (`Login.vue` y `Register.vue`):
1. **Form Data**: Agrega la propiedad `'cf-turnstile-response'` en tu objeto `useForm`:
   ```javascript
   const form = useForm({
       email: '',
       password: '',
       remember: false,
       'cf-turnstile-response': '', // Guardará el token generado
   });
   ```

2. **Renderizado del Widget**:
   Inserta el contenedor HTML del captcha. En Vue 3 se recomienda escuchar la devolución de llamada del token (`data-callback`) para asignarlo al formulario:
   
   Coloca esto en el template de tu formulario (ej. antes del botón submit):
   ```html
   <div class="mt-4 flex justify-center flex-col items-center">
       <div 
           class="cf-turnstile" 
           :data-sitekey="$page.props.turnstile_site_key"
           data-callback="onTurnstileSuccess"
       ></div>
       <!-- Mostrar errores de validación de Inertia -->
       <span v-if="form.errors['cf-turnstile-response']" class="text-sm text-red-600 mt-2">
           {{ form.errors['cf-turnstile-response'] }}
       </span>
   </div>
   ```

3. **Mapeo del Token en JS**:
   Crea la función global (o asóciala al objeto `window`) para capturar el token que genera Turnstile:
   ```javascript
   import { onMounted } from 'vue';

   onMounted(() => {
       // Cloudflare Turnstile llama a esta función con el token cuando el usuario pasa la validación
       window.onTurnstileSuccess = (token) => {
           form['cf-turnstile-response'] = token;
       };
   });
   ```

4. **Pasar la clave pública a Inertia**:
   En `app/Http/Middleware/HandleInertiaRequests.php`, comparte la clave pública del captcha (`site_key`) para que esté accesible desde Vue a través de `$page.props`:
   ```php
   public function share(Request $request): array
   {
       return array_merge(parent::share($request), [
           'turnstile_site_key' => config('services.turnstile.site_key'),
       ]);
   }
   ```

5. **Manejo de Errores de Validación (incluyendo Rate Limiting)**:
   Inertia enviará los errores automáticamente y se mapearán a `form.errors.email` (para el Rate Limit) y a `form.errors['cf-turnstile-response']` (para el captcha). El componente Vue ya los pintará usando los componentes `<InputError :message="form.errors.email" />`.
