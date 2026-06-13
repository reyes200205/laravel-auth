# Guía de Pruebas de Autenticación (Test Auth)

Esta guía detalla las fases y los casos de prueba para validar el sistema de autenticación, MFA (OTP + Geolocalización), y registro.

---

## 📋 Fase 1: Autenticación Básica (Login Estándar)

Esta fase valida que el formulario de inicio de sesión controle correctamente las credenciales y el flujo inicial por rol.

### Casos de Prueba:

*   **Caso 1.1: Intento de login con campos vacíos**
    *   **Acción:** Dejar los campos de correo y contraseña vacíos y hacer clic en "Iniciar Sesión".
    *   **Resultado esperado:** El sistema no envía la petición o retorna errores de validación de Laravel ("El campo correo electrónico es obligatorio", "El campo contraseña es obligatorio").
*   **Caso 1.2: Contraseña incorrecta**
    *   **Acción:** Ingresar un correo válido registrado pero con una contraseña incorrecta.
    *   **Resultado esperado:** Mensaje de error: *"Estas credenciales no coinciden con nuestros registros."* y el usuario permanece en la pantalla de login.
*   **Caso 1.3: Correo no registrado**
    *   **Acción:** Intentar iniciar sesión con un correo electrónico que no existe en la base de datos.
    *   **Resultado esperado:** Mensaje de error de credenciales incorrectas.
*   **Caso 1.4: Login exitoso - Usuario Estándar (Sin MFA)**
    *   **Acción:** Iniciar sesión con las credenciales de un usuario con rol `user` (ej. `user@example.com`).
    *   **Resultado esperado:** Redirección directa al Dashboard del usuario sin pasar por pantallas adicionales de verificación.
*   **Caso 1.5: Login exitoso - Super Administrador (MFA Requerido)**
    *   **Acción:** Iniciar sesión con las credenciales de un usuario con rol `super-admin` (ej. `admin@example.com`).
    *   **Resultado esperado:** El sistema detecta que el rol requiere múltiples factores de autenticación y lo redirige a la ruta `/auth/mfa` (Pantalla de ingreso de código OTP y Geolocalización).

---

## 🔑 Fase 2: Segundo Factor de Autenticación (MFA - Código OTP)

Esta fase valida el envío, reenvío y la verificación del código de un solo uso enviado al administrador.

### Casos de Prueba:

*   **Caso 2.1: Intento de acceso directo a la pantalla MFA**
    *   **Acción:** Intentar acceder directamente a la URL `/auth/mfa` sin haber realizado el paso de inicio de sesión previo.
    *   **Resultado esperado:** Redirección automática a la pantalla de Login (protección por middleware/sesión incompleta).
*   **Caso 2.2: Código OTP vacío o incompleto**
    *   **Acción:** En la pantalla de MFA, hacer clic en verificar dejando el código en blanco o con menos de 6 caracteres.
    *   **Resultado esperado:** Mensaje de validación indicando que el código es requerido y debe constar de 6 dígitos.
*   **Caso 2.3: Código OTP incorrecto o expirado**
    *   **Acción:** Introducir un código numérico aleatorio (ej. `123456`) que no coincida con el generado por el sistema, o ingresar el código correcto una vez vencido el tiempo de validez.
    *   **Resultado esperado:** Mensaje de error: *"El código ingresado es inválido o ha expirado."* El intento se registra en el log.
*   **Caso 2.4: Reenvío de código OTP (Throttle/Límite)**
    *   **Acción:** Hacer clic en "Reenviar Código" varias veces seguidas de forma repetitiva.
    *   **Resultado esperado:** El sistema debe limitar la frecuencia del reenvío de códigos de un solo uso (MFA Code Throttle) y retornar un error de límite de intentos.

---

## 📍 Fase 3: Tercer Factor de Autenticación (MFA - Geolocalización)

Esta fase valida la restricción geográfica basándose en las coordenadas del navegador del Super Administrador.

### Casos de Prueba:

*   **Caso 3.1: Coordenadas fuera del radio permitido**
    *   **Acción:** Proporcionar al navegador coordenadas lejanas a las oficinas geocercadas autorizadas (UTT o Casa) y enviar el formulario con un OTP correcto.
    *   **Resultado esperado:** 
        *   Denegación del acceso.
        *   Registro real del intento fallido por ubicación en el log (`auth.log`).
        *   Mensaje de error en la interfaz indicando que no se encuentra dentro del rango de ubicación permitido.
*   **Caso 3.2: Coordenadas dentro del radio permitido (UTT / Casa)**
    *   **Acción:** Permitir la lectura de ubicación del navegador y simular/proporcionar coordenadas que se encuentren dentro del radio de 1200 metros (1.2 km) de la UTT o el radio autorizado para Casa.
    *   **Resultado esperado:**
        *   Acceso exitoso al sistema.
        *   Redirección al Dashboard de Super Administrador.
        *   No se registran advertencias falsas de ubicación en el historial de logs de auditoría.

---

## 📝 Fase 4: Registro y Verificación de Correo

Esta fase valida el flujo de creación de cuentas y la restricción de acceso hasta verificar el correo electrónico.

### Casos de Prueba:

*   **Caso 4.1: Registro de nuevo usuario con Turnstile**
    *   **Acción:** Completar el formulario en `/register` introduciendo datos válidos y completando el desafío Cloudflare Turnstile.
    *   **Resultado esperado:** Creación exitosa de la cuenta en base de datos y redirección automática a la vista de verificación de correo.
*   **Caso 4.2: Restricción de acceso sin verificación**
    *   **Acción:** Tras registrarse (o si un usuario no verificado inicia sesión), intentar acceder directamente a `/dashboard`.
    *   **Resultado esperado:** Redirección automática a la vista `/verify-email`.
*   **Caso 4.3: Verificación exitosa**
    *   **Acción:** Hacer clic en el enlace de verificación firmado que se envía al correo del usuario.
    *   **Resultado esperado:** Activación de la cuenta, cambio de estado de verificación en base de datos y redirección con éxito al Dashboard.

---

## 🛠️ Cómo ejecutar las pruebas automáticas

Si deseas comprobar que todos estos flujos y restricciones a nivel de código se ejecutan correctamente sin realizar pruebas manuales, puedes correr el comando en consola:

```bash
php artisan test
```

Este comando evaluará automáticamente los 27 escenarios de prueba críticos del backend.
