<?php

namespace Tests\Feature\Auth;

use App\Models\Office;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Database\Seeders\RolesAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\OneTimePasswords\Models\OneTimePassword;
use Tests\TestCase;

class MfaLocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sembrar los roles y permisos para las pruebas
        $this->seed(RolesAndPermissionSeeder::class);
    }

    public function test_super_admin_requires_three_factors_and_can_authenticate_with_valid_coordinates(): void
    {
        // 1. Crear oficina de prueba (UTT)
        $officeUtt = Office::create([
            'name' => 'UTT',
            'latitude' => 19.43260770,
            'longitude' => -99.13320800,
            'radius' => 200, // 200 metros
            'allowed_ips' => '192.168.1.100,10.0.0.1',
        ]);

        // 2. Crear usuario administrador
        $user = User::create([
            'name' => 'Admin UTT',
            'email' => 'utt@gmail.com',
            'password' => Hash::make('password'),
            'office_id' => $officeUtt->id,
        ]);
        $user->assignRole('super-admin');

        // Limpiar caché de permisos de Spatie
        $this->app->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // 3. POST Login (Factor 1: Contraseña)
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('auth.mfa'));
        $this->assertGuest(); // Aún no está logueado oficialmente
        $this->assertEquals($user->id, session('mfa:user_id'));
        $this->assertEquals('email_otp', session('mfa:active_method'));

        // 4. POST OTP de correo (Factor 2: Email OTP)
        $otp = OneTimePassword::where('authenticatable_id', $user->id)->latest()->first();
        $this->assertNotNull($otp);

        $responseMfa1 = $this->post('/auth/mfa', [
            'code' => $otp->password,
        ]);

        // Debería redirigir de nuevo a la pantalla de MFA con el siguiente factor activo: location
        $responseMfa1->assertRedirect(route('auth.mfa'));
        $this->assertGuest();
        $this->assertEquals('location', session('mfa:active_method'));

        // 5. POST Ubicación - IP no autorizada, Coordenadas válidas (Factor 3: Location/IP - Falla por IP)
        // Enviamos coordenadas correctas pero desde una IP que no está permitida para UTT
        $responseMfaLocFailIp = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.5'])
            ->post('/auth/mfa', [
                'latitude' => 19.43260770,
                'longitude' => -99.13320800,
            ]);

        $responseMfaLocFailIp->assertSessionHasErrors(['latitude']);
        $this->assertGuest();

        // 6. POST Ubicación - IP autorizada, Coordenadas inválidas (Factor 3: Location/IP - Falla por GPS)
        // Enviamos IP correcta pero coordenadas lejanas (ej. Nueva York)
        $responseMfaLocFailGps = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->post('/auth/mfa', [
                'latitude' => 40.712776,
                'longitude' => -74.005974,
            ]);

        $responseMfaLocFailGps->assertSessionHasErrors(['latitude']);
        $this->assertGuest();

        // 7. POST Ubicación - IP autorizada, Coordenadas válidas (Factor 3: Location/IP - Éxito)
        // Enviamos IP y coordenadas correctas para UTT
        $responseMfaLocSuccess = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->post('/auth/mfa', [
                'latitude' => 19.43260770,
                'longitude' => -99.13320800,
            ]);

        // Debería loguear exitosamente y redirigir al HOME
        $responseMfaLocSuccess->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($user);
    }

    public function test_regular_user_requires_only_two_factors(): void
    {
        // 1. Crear usuario regular
        $user = User::create([
            'name' => 'Regular User',
            'email' => 'user@gmail.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('user');

        // 2. POST Login (Factor 1: Contraseña)
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('auth.mfa'));
        $this->assertGuest();
        $this->assertEquals('email_otp', session('mfa:active_method'));

        // 3. POST OTP de correo (Factor 2: Email OTP)
        $otp = OneTimePassword::where('authenticatable_id', $user->id)->latest()->first();
        $this->assertNotNull($otp);

        $responseMfa = $this->post('/auth/mfa', [
            'code' => $otp->password,
        ]);

        // El usuario regular no requiere ubicación, por lo que entra al HOME de inmediato
        $responseMfa->assertRedirect(RouteServiceProvider::HOME);
        $this->assertAuthenticatedAs($user);
    }
}
