<?php

namespace App\Http\Controllers;

use App\Models\CommunityAttendance;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunityAttendanceAudioController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, CommunityAttendance $attendance): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user, 403);
        abort_unless(
            (int) $user->employee_id === (int) $attendance->employee_id
            || in_array($user->role, ['admin', 'administrator', 'super_admin'], true),
            403,
        );
        abort_unless(filled($attendance->closing_audio_path), 404);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        abort_unless($disk->exists($attendance->closing_audio_path), 404);

        return $disk->response(
            $attendance->closing_audio_path,
            basename($attendance->closing_audio_path),
            ['Content-Type' => $attendance->closing_audio_mime_type ?: 'audio/webm'],
        );
    }
}
