<?php

declare(strict_types=1);

namespace App\NativeComponents\StaffAccess;

use App\Services\Staff\StaffApiClient;
use Illuminate\View\View;
use Native\Mobile\Attributes\OnNative;
use Native\Mobile\Edge\NativeComponent;
use Native\Mobile\Events\Camera\PhotoTaken;
use Native\Mobile\Events\Microphone\MicrophoneRecorded;
use Native\Mobile\Facades\Camera;
use Native\Mobile\Facades\Microphone;
use Throwable;

class ReportScreen extends NativeComponent
{
    public int $sessionId;

    public ?array $session = null;

    public ?string $voicePath = null;

    public array $photos = [];

    public bool $recording = false;

    public ?string $error = null;

    public bool $loading = false;

    public function mount(): void
    {
        if (app(StaffApiClient::class)->getToken() === null) {
            $this->replace('/staff/login');

            return;
        }

        $this->sessionId = (int) $this->param('sessionId');
        $this->loadSession();
    }

    public function loadSession(): void
    {
        $api = app(StaffApiClient::class);
        $this->loading = true;

        try {
            $result = $api->finishSession($this->sessionId);
            $this->session = $result['session'];
        } catch (Throwable $exception) {
            $this->error = 'No se pudo cargar la sesión.';
        } finally {
            $this->loading = false;
        }
    }

    public function startRecording(): void
    {
        $this->recording = true;
        Microphone::record()->id('staff-report-'.$this->sessionId)->start();
    }

    public function stopRecording(): void
    {
        Microphone::stop();
        $this->recording = false;
    }

    #[OnNative(MicrophoneRecorded::class)]
    public function handleVoiceRecorded(string $path, string $mimeType, ?string $id): void
    {
        if ($id === 'staff-report-'.$this->sessionId) {
            $this->voicePath = $path;
        }
    }

    public function takePhoto(): void
    {
        Camera::getPhoto()->id('staff-photo-'.$this->sessionId);
    }

    #[OnNative(PhotoTaken::class)]
    public function handlePhotoTaken(string $path, string $mimeType, ?string $id): void
    {
        if ($id === 'staff-photo-'.$this->sessionId) {
            $this->photos[] = $path;
        }
    }

    public function clearVoice(): void
    {
        $this->voicePath = null;
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function submitReport(): void
    {
        $api = app(StaffApiClient::class);
        $this->error = null;
        $this->loading = true;

        try {
            $api->submitReport($this->sessionId, $this->voicePath, $this->photos);
            $this->replace('/staff/dashboard');
        } catch (Throwable $exception) {
            $this->error = 'No se pudo enviar el parte. Revisa los adjuntos requeridos.';
        } finally {
            $this->loading = false;
        }
    }

    public function render(): View
    {
        return view('native.staff-access.report-screen');
    }
}
