<?php

namespace App\Actions\Community;

use Laravel\Ai\Transcription;

class TranscribeAttendanceAudio
{
    public function handle(string $path, string $disk = 'local'): string
    {
        return trim((string) Transcription::fromStorage($path, $disk)
            ->language('es')
            ->timeout(60)
            ->generate());
    }
}
