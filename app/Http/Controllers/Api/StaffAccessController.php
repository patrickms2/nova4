<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Staff\CompleteStaffAccess;
use App\Actions\Staff\RequestStaffAccessFinish;
use App\Actions\Staff\StaffAccessDeniedException;
use App\Actions\Staff\StartStaffAccess;
use App\Actions\Staff\SubmitWorkReport;
use App\Actions\Staff\WorkReportRejectedException;
use App\Enums\WorkSessionStatus;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\User;
use App\Models\WorkSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StaffAccessController
{
    /**
     * Issue a Sanctum token for a staff member.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son válidas.'],
            ]);
        }

        $token = $user->createToken($credentials['device_name'])->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->getFilamentName(),
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * List the active access grants for the authenticated staff member.
     */
    public function grants(Request $request): JsonResponse
    {
        $grants = AccessGrant::with('accessPoints')
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->get();

        return response()->json([
            'grants' => $grants->map(fn (AccessGrant $grant) => [
                'id' => $grant->id,
                'property_id' => $grant->property_id,
                'name' => $grant->name,
                'role' => $grant->role,
                'report_required' => $grant->report_required,
                'voice_required' => $grant->voice_required,
                'photo_required' => $grant->photo_required,
                'minimum_photos' => $grant->minimum_photos,
                'access_points' => $grant->accessPoints->map(fn (AccessPoint $point) => [
                    'id' => $point->id,
                    'name' => $point->name,
                    'type' => $point->type?->value,
                    'location' => $point->location,
                ])->values(),
            ]),
        ]);
    }

    /**
     * Return the current active session for the authenticated user, if any.
     */
    public function currentSession(Request $request): JsonResponse
    {
        $session = WorkSession::with(['accessGrant', 'accessPoint', 'workReport'])
            ->where('user_id', $request->user()->id)
            ->whereNotIn('status', [WorkSessionStatus::Finished->value])
            ->latest('started_at')
            ->first();

        if (! $session) {
            return response()->json(['session' => null]);
        }

        return response()->json([
            'session' => $this->serializeSession($session),
        ]);
    }

    /**
     * Start a new staff access session.
     */
    public function start(Request $request, StartStaffAccess $startAction): JsonResponse
    {
        $data = $request->validate([
            'access_grant_id' => ['required', 'integer', 'exists:access_grants,id'],
            'access_point_id' => ['required', 'integer', 'exists:access_points,id'],
        ]);

        $grant = AccessGrant::with('accessPoints')
            ->where('user_id', $request->user()->id)
            ->findOrFail($data['access_grant_id']);

        $accessPoint = AccessPoint::findOrFail($data['access_point_id']);

        try {
            $session = $startAction->handle($request->user(), $grant, $accessPoint);
        } catch (StaffAccessDeniedException $exception) {
            return response()->json([
                'success' => false,
                'reason' => $exception->getReason(),
                'message' => $this->denialMessage($exception->getReason()),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'session' => $this->serializeSession($session),
        ]);
    }

    /**
     * Request to finish the current session.
     */
    public function finish(Request $request, WorkSession $session, RequestStaffAccessFinish $finishAction): JsonResponse
    {
        $this->ensureOwnsSession($request, $session);

        $session = $finishAction->handle($session);

        return response()->json([
            'session' => $this->serializeSession($session),
            'report_required' => $session->accessGrant->report_required,
        ]);
    }

    /**
     * Submit a work report with optional voice and photos.
     */
    public function submitReport(Request $request, WorkSession $session, SubmitWorkReport $submitAction): JsonResponse
    {
        $this->ensureOwnsSession($request, $session);

        $request->validate([
            'voice' => ['nullable', 'file', 'mimetypes:audio/*'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:10240'],
        ]);

        $voicePath = null;
        if ($request->hasFile('voice')) {
            $voicePath = $this->storeUploadedFile($request->file('voice'), 'voice', $session);
        }

        $photoPaths = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $photoPaths[] = $this->storeUploadedFile($photo, "photo-{$index}", $session);
            }
        }

        try {
            $report = $submitAction->handle($session, $voicePath, $photoPaths);
        } catch (WorkReportRejectedException $exception) {
            return response()->json([
                'success' => false,
                'reason' => $exception->getReason(),
                'message' => $this->rejectionMessage($exception->getReason()),
            ], 422);
        }

        $session->refresh();

        return response()->json([
            'success' => true,
            'report' => [
                'id' => $report->id,
                'summary' => $report->summary,
                'voice_transcription' => $report->voice_transcription,
                'photos' => $report->photos,
            ],
            'session' => $this->serializeSession($session),
        ]);
    }

    /**
     * Complete the session without a report.
     */
    public function complete(Request $request, WorkSession $session, CompleteStaffAccess $completeAction): JsonResponse
    {
        $this->ensureOwnsSession($request, $session);

        $session = $completeAction->handle($session);

        return response()->json([
            'session' => $this->serializeSession($session),
        ]);
    }

    /**
     * Show a single session.
     */
    public function show(Request $request, WorkSession $session): JsonResponse
    {
        $this->ensureOwnsSession($request, $session);

        return response()->json([
            'session' => $this->serializeSession($session),
        ]);
    }

    private function ensureOwnsSession(Request $request, WorkSession $session): void
    {
        if ($session->user_id !== $request->user()->id) {
            abort(Response::HTTP_FORBIDDEN, 'No puedes gestionar esta sesión.');
        }
    }

    private function serializeSession(WorkSession $session): array
    {
        return [
            'id' => $session->id,
            'property_id' => $session->property_id,
            'access_grant_id' => $session->access_grant_id,
            'access_point_id' => $session->access_point_id,
            'status' => $session->status->value,
            'status_label' => $session->status->getLabel(),
            'started_at' => $session->started_at?->toIso8601String(),
            'finished_at' => $session->finished_at?->toIso8601String(),
            'elapsed_seconds' => $session->elapsedSeconds(),
            'access_point' => $session->accessPoint?->name,
            'grant' => $session->accessGrant?->name,
            'report' => $session->workReport ? [
                'id' => $session->workReport->id,
                'summary' => $session->workReport->summary,
                'photos' => $session->workReport->photos,
            ] : null,
        ];
    }

    private function storeUploadedFile($file, string $prefix, WorkSession $session): string
    {
        $path = $file->store("staff-reports/{$session->id}", 'public');

        return Storage::disk('public')->path($path);
    }

    private function denialMessage(string $reason): string
    {
        return match ($reason) {
            'not_owner' => 'El permiso no te pertenece.',
            'inactive' => 'El permiso no está activo.',
            'revoked' => 'El permiso ha sido revocado.',
            'outside_date_range' => 'El permiso no es válido en esta fecha.',
            'weekday_not_allowed' => 'Hoy no es un día permitido.',
            'time_not_allowed' => 'Ahora no estás dentro del horario permitido.',
            'access_point_not_allowed' => 'Este punto de acceso no está autorizado.',
            'active_session_exists' => 'Ya tienes una sesión activa.',
            default => 'Acceso denegado.',
        };
    }

    private function rejectionMessage(string $reason): string
    {
        return match ($reason) {
            'voice_required' => 'Se requiere una nota de voz.',
            'photo_requirement_not_met' => 'No se cumple el mínimo de fotos requeridas.',
            'session_not_awaiting_report' => 'La sesión no está esperando un informe.',
            default => 'El informe no ha sido aceptado.',
        };
    }
}
