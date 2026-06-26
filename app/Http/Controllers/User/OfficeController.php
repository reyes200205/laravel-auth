<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para gestionar la edición de la configuración de la Oficina asignada al usuario.
 *
 * Permite a los usuarios autorizados (rol 'user') actualizar las coordenadas GPS,
 * el radio de tolerancia y las direcciones IP permitidas de la oficina. Registra
 * detalladamente las modificaciones en un canal de logs específico.
 */
class OfficeController extends Controller
{
    /**
     * Actualiza la configuración de ubicación e IPs de la oficina asignada.
     *
     * Valida los datos provistos y, si la oficina corresponde a la del usuario autenticado,
     * guarda los cambios. Registra un log detallado del cambio en 'office.log'.
     *
     * @param \Illuminate\Http\Request $request Solicitud HTTP con los nuevos parámetros de la oficina.
     * @param \App\Models\Office $office Modelo de la oficina que se desea actualizar.
     * @return \Illuminate\Http\RedirectResponse Redirección hacia atrás con mensaje de éxito o error.
     */
    public function update(Request $request, Office $office): RedirectResponse
    {
        $user = $request->user();

        // 1. Validar que el usuario logueado pertenezca a la oficina o tenga el rol de 'user'
        if (!$user->hasRole('user') || $user->office_id !== $office->id) {
            Log::channel('office')->warning('Intento de edición no autorizado para la oficina.', [
                'user' => $user->email,
                'office_id' => $office->id,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors([
                'error' => 'No estás autorizado para modificar esta oficina.'
            ]);
        }

        // 2. Validar los datos de entrada
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'required|integer|min:1|max:10000',
            'allowed_ips' => 'required|string',
        ], [
            'latitude.required' => 'La latitud es requerida.',
            'latitude.numeric' => 'La latitud debe ser un número decimal.',
            'latitude.between' => 'La latitud debe estar entre -90 y 90 grados.',
            'longitude.required' => 'La longitud es requerida.',
            'longitude.numeric' => 'La longitud debe ser un número decimal.',
            'longitude.between' => 'La longitud debe estar entre -180 y 180 grados.',
            'radius.required' => 'El radio de tolerancia es requerido.',
            'radius.integer' => 'El radio debe ser un número entero en metros.',
            'radius.min' => 'El radio debe ser de al menos 1 metro.',
            'allowed_ips.required' => 'Debes configurar al menos una dirección IP (o rango/local).',
        ]);

        // 3. Validar que no se ingresen direcciones IPv6
        $ips = array_map('trim', explode(',', $request->input('allowed_ips', '')));
        foreach ($ips as $ipAddress) {
            if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return back()->withErrors([
                    'allowed_ips' => 'Solo se permiten direcciones IPv4. La dirección IPv6 "' . $ipAddress . '" no está permitida.'
                ]);
            }
        }

        // Guardar valores anteriores para registrar en el log
        $oldData = [
            'latitude' => $office->latitude,
            'longitude' => $office->longitude,
            'radius' => $office->radius,
            'allowed_ips' => $office->allowed_ips,
        ];

        // 3. Actualizar la oficina
        $office->update($validated);

        // 4. Registrar la acción en el archivo log personalizado 'office.log'
        Log::channel('office')->info('Configuración de oficina actualizada exitosamente.', [
            'user' => $user->email,
            'office_id' => $office->id,
            'office_name' => $office->name,
            'old_values' => $oldData,
            'new_values' => $validated,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'datetime' => now()->toDateTimeString(),
        ]);

        return back()->with('status', 'La configuración de la oficina se ha actualizado correctamente.');
    }
}
