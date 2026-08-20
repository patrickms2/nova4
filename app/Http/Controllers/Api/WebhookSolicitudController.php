<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\SolicitudTaxiRecibida;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookSolicitudController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = $request->header('X-Webhook-Token');

        if ($token !== config('services.taxisns.webhook_token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'tipo' => 'required|string|in:nueva,actualizada,cancelada',
            'solicitud' => 'required|array',
            'solicitud.codservicio' => 'required|integer',
            'solicitud.nombreUsuario' => 'nullable|string',
            'solicitud.codusuario' => 'nullable|integer',
            'solicitud.codmunicipio' => 'nullable|integer',
            'solicitud.nombreMunicipio' => 'nullable|string',
            'solicitud.codestado' => 'nullable|integer',
            'solicitud.nombreEstado' => 'nullable|string',
            'solicitud.fecha_servicio' => 'nullable|string',
            'solicitud.habitacion' => 'nullable',
            'solicitud.personas' => 'nullable|integer',
            'solicitud.nombre' => 'nullable|string',
            'solicitud.codtipotaxi' => 'nullable|integer',
            'solicitud.nombreTipo' => 'nullable|string',
        ]);

        SolicitudTaxiRecibida::dispatch(
            solicitud: $data['solicitud'],
            tipo: $data['tipo'],
        );

        return response()->json([
            'status' => 'ok',
            'message' => 'Evento broadcast enviado',
        ]);
    }
}
