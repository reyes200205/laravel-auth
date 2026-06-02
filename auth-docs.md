# Sistema de Autenticación - Documentación del Proyecto

Este documento detalla la arquitectura, características de seguridad, limitadores de peticiones (Rate Limiting), control de acceso y registro de logs implementados en el sistema de autenticación de este proyecto (Laravel + Inertia.js + Vue 3).

---

## 1. Stack Tecnológico

El flujo de autenticación está construido sobre la base de:
*   **Backend:** [Laravel 10.x](https://laravel.com/)
*   **Frontend:** [Vue 3](https://vuejs.org/) con script setup y composición.
*   **Adaptador/Comunicación:** [Inertia.js](https://inertiajs.com/) para una SPA fluida sin construir una API externa.
*   **Estilos:** [Tailwind CSS](https://tailwindcss.com/) con diseño adaptado a modo claro y oscuro.

---

## 2. Características de Seguridad en el Registro y Login

### A. Validación de Fuerza de Contraseña
Durante el registro de un nuevo usuario, se aplica una validación rigurosa tanto en backend como en frontend.

*   **Regla de Validación (Backend):**
    En [RegisteredUserController.php](file:///c:/laragon/www/laravel-auth/app/Http/Controllers/Auth/RegisteredUserController.php) se utiliza la siguiente expresión regular:
    `'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#.\-_]).{8,}$/'`
    *   Mínimo 8 caracteres.
    *   Al menos una letra mayúscula (A-Z).
    *   Al menos una letra minúscula (a-z).
    *   Al menos un número (0-9).
    *   Al menos un carácter especial (`@$!%*?&#.-_`).
*   **Componente Interactivo de UI:**
    En [register.vue](file:///c:/laragon/www/laravel-auth/resources/js/Pages/auth/register.vue) existe un medidor dinámico que evalúa la contraseña en tiempo real mientras el usuario escribe, mostrando una barra de progreso que va de **Vacía / Débil / Media** a **Fuerte** y una cuadrícula minimalista indicando los requisitos cumplidos.

### B. Integración con Cloudflare Turnstile (CAPTCHA)
Para evitar registros y logins automáticos por bots, se ha integrado Cloudflare Turnstile.
*   **Regla de Validación:**
    La regla personalizada [Turnstile.php](file:///c:/laragon/www/laravel-auth/app/Rules/Turnstile.php) verifica el token del captcha contra la API de Cloudflare (`https://challenges.cloudflare.com/turnstile/v0/siteverify`) utilizando la clave secreta configurada.
*   **Formularios:**
    Los formularios de **Login** y **Registro** renderizan dinámicamente el widget utilizando la clave de sitio provista desde Laravel a través de las propiedades globales de Inertia (`page.props.turnstile_site_key`).
*   **Activación/Desactivación:**
    Es controlable desde la configuración (`config/services.php`) para facilitar la realización de tests automatizados sin necesidad de resolver captchas.

---

## 3. Limitador de Peticiones Personalizado (Custom Rate Limiter)

Para proteger la aplicación contra ataques de fuerza bruta y denegación de servicio en las rutas críticas, se ha implementado un middleware personalizado.

*   **Middleware:** [CustomRateLimiter.php](file:///c:/laragon/www/laravel-auth/app/Http/Middleware/CustomRateLimiter.php)
*   **Identificador:** Se genera combinando una clave única de la acción y la dirección IP del cliente: `"{keyName}|{ip}"`.
*   **Manejo de Excesos (429 Too Many Requests):**
    *   **Inertia:** Si la petición viene de Inertia, se retorna la vista personalizada [TooManyRequests.vue](file:///c:/laragon/www/laravel-auth/resources/js/Pages/Errors/TooManyRequests.vue) que muestra un contador regresivo con los segundos restantes para volver a intentar.
    *   **JSON/API:** Retorna una respuesta JSON estructurada con código de estado `429`.
    *   **Petición Web Tradicional:** Lanza un aborto HTTP `429` nativo.

### Rutas Protegidas en [routes/auth.php](file:///c:/laragon/www/laravel-auth/routes/auth.php):
*   **Registro (`POST /register`):** Máximo **3 intentos por minuto**.
*   **Login (`POST /login`):** Máximo **5 intentos por minuto**.

---

## 4. Roles y Permisos (RBAC)

Se utiliza el paquete oficial de Spatie (`spatie/laravel-permission`) para gestionar el control de acceso basado en roles.

### A. Roles y Permisos Definidos
Configurados en el seeder [RolesAndPermissionSeeder.php](file:///c:/laragon/www/laravel-auth/database/seeders/RolesAndPermissionSeeder.php):
*   **Permisos:**
    *   `users.view`
    *   `users.create`
    *   `users.edit`
    *   `users.delete`
*   **Roles:**
    *   `super-admin`: Tiene asignados todos los permisos de usuario.
    *   `user`: Tiene permisos para ver y editar (`users.view`, `users.edit`).
    *   `guest`: Solo tiene permiso de visualización (`users.view`).

### B. Asignación al Registro
Por defecto, cuando un nuevo usuario se registra a través de [RegisteredUserController.php](file:///c:/laragon/www/laravel-auth/app/Http/Controllers/Auth/RegisteredUserController.php), se le asigna el rol de **`guest`**:
```php
$user->assignRole('guest');
```

---

## 5. Auditoría y Logs de Registro

El sistema registra eventos clave en archivos de log para auditorías de seguridad.

*   **Canal Custom:** Se configuró un canal `'session'` en [logging.php](file:///c:/laragon/www/laravel-auth/config/logging.php) que escribe en `storage/logs/session.log`.
*   **Evento de Registro:**
    Al completarse con éxito un registro de usuario, se almacena una entrada de log detallada:
    ```php
    Log::channel('session')->info('User registered successfully', [
        'user' => $user->email,
        'name' => $user->name,
        'user_id' => $user->id,
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);
    ```

---

## 6. Estructura de Rutas de Autenticación ([routes/auth.php](file:///c:/laragon/www/laravel-auth/routes/auth.php))

### Rutas para Invitados (Middleware `guest`)
*   `GET /register` & `POST /register` — Registro de usuarios (con limitación de 3 intentos/min).
*   `GET /login` & `POST /login` — Inicio de sesión (con limitación de 5 intentos/min).
*   `GET /forgot-password` & `POST /forgot-password` — Solicitud de restablecimiento de contraseña.
*   `GET /reset-password/{token}` & `POST /reset-password` — Actualización a la nueva contraseña.

### Rutas para Usuarios Autenticados (Middleware `auth`)
*   `GET /verify-email` & `GET /verify-email/{id}/{hash}` — Verificación del correo electrónico.
*   `POST /email/verification-notification` — Reenvío del correo de verificación.
*   `GET /confirm-password` & `POST /confirm-password` — Confirmación de contraseña antes de acciones seguras.
*   `PUT /password` — Actualización de la contraseña actual del perfil.
*   `POST /logout` — Cierre de sesión.
