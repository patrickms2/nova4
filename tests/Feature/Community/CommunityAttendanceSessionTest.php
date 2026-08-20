<?php

namespace Tests\Feature\Community;

use App\Actions\Community\TranscribeAttendanceAudio;
use App\Models\CommunityAttendance;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Laravel\Ai\Transcription;
use Tests\TestCase;

class CommunityAttendanceSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->nullable();
            $table->foreignId('employee_id')->nullable();
            $table->timestamps();
        });
        Schema::create('community_attendances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_id')->nullable();
            $table->foreignId('community_department_id')->nullable();
            $table->foreignId('employee_id');
            $table->foreignId('community_shift_id')->nullable();
            $table->date('attendance_date');
            $table->dateTime('checked_in_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_in_accuracy')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->unsignedInteger('check_out_accuracy')->nullable();
            $table->string('type')->default('presence');
            $table->string('status')->default('recorded');
            $table->text('notes')->nullable();
            $table->string('closing_audio_path')->nullable();
            $table->string('closing_audio_mime_type')->nullable();
            $table->string('transcription_status')->nullable();
            $table->text('transcription_error')->nullable();
            $table->foreignId('recorded_by')->nullable();
            $table->timestamps();
        });
    }

    public function test_audio_is_transcribed_in_spanish_from_private_storage(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('comunigest/attendance-audio/session.webm', 'audio');
        Event::fake();
        Transcription::fake(['Trabajo finalizado sin incidencias.']);

        $notes = app(TranscribeAttendanceAudio::class)->handle('comunigest/attendance-audio/session.webm');

        $this->assertSame('Trabajo finalizado sin incidencias.', $notes);
        Transcription::assertGenerated(fn ($prompt): bool => $prompt->language === 'es');
    }

    public function test_admin_can_play_the_original_private_audio(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('comunigest/attendance-audio/session.webm', 'original-audio');
        $admin = User::query()->create(['name' => 'Admin', 'email' => 'admin@example.test', 'role' => 'admin']);
        $attendance = CommunityAttendance::query()->create([
            'employee_id' => 42,
            'attendance_date' => today(),
            'closing_audio_path' => 'comunigest/attendance-audio/session.webm',
            'closing_audio_mime_type' => 'audio/webm',
        ]);

        $response = $this->actingAs($admin)->get(route('comunigest.attendances.audio', $attendance));

        $response->assertOk()->assertHeader('content-type', 'audio/webm');
        $this->assertSame('original-audio', $response->streamedContent());
    }

    public function test_unrelated_employee_cannot_play_an_attendance_audio(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('comunigest/attendance-audio/session.webm', 'audio');
        $employee = User::query()->create(['name' => 'Employee', 'email' => 'employee@example.test', 'role' => 'employee', 'employee_id' => 7]);
        $attendance = CommunityAttendance::query()->create([
            'employee_id' => 42,
            'attendance_date' => today(),
            'closing_audio_path' => 'comunigest/attendance-audio/session.webm',
        ]);

        $this->actingAs($employee)
            ->get(route('comunigest.attendances.audio', $attendance))
            ->assertForbidden();
    }

    public function test_portal_requests_location_community_and_closing_audio(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));

        $this->assertStringContainsString('navigator.geolocation.getCurrentPosition', $view);
        $this->assertStringContainsString('wire:model="attendanceCommunityIds"', $view);
        $this->assertStringContainsString('type="checkbox"', $view);
        $this->assertStringContainsString('navigator.mediaDevices.getUserMedia({ audio: true })', $view);
        $this->assertStringContainsString('new MediaRecorder(this.microphoneStream', $view);
        $this->assertStringContainsString("this.\$wire.\$upload(\n                            'attendanceAudio'", $view);
        $this->assertStringNotContainsString("this.\$wire.upload(\n                            'attendanceAudio'", $view);
        $this->assertStringNotContainsString('wire:model="attendanceAudio"', $view);
    }

    public function test_temporary_upload_limit_supports_attendance_audio_and_photo_limits(): void
    {
        $rules = config('livewire.temporary_file_upload.rules');

        $this->assertContains('file', $rules);
        $this->assertContains('max:25600', $rules);
    }

    public function test_recorded_audio_passes_validation_and_incident_photo_is_optional(): void
    {
        $audio = UploadedFile::fake()->create('cierre-sesion.webm', 1024, 'video/webm');
        $photo = UploadedFile::fake()->image('incidencia.jpg')->size(1024);

        $audioValidator = Validator::make(
            ['attendanceAudio' => $audio],
            ['attendanceAudio' => ['required', 'file', 'mimetypes:audio/*,video/webm,video/mp4', 'max:25600']],
        );
        $photoValidator = Validator::make(
            ['entryFile' => $photo],
            ['entryFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']],
        );
        $missingPhotoValidator = Validator::make(
            ['entryFile' => null],
            ['entryFile' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']],
        );

        $this->assertFalse($audioValidator->fails(), $audioValidator->errors()->first());
        $this->assertFalse($photoValidator->fails(), $photoValidator->errors()->first());
        $this->assertFalse($missingPhotoValidator->fails(), $missingPhotoValidator->errors()->first());
    }

    public function test_livewire_upload_signature_uses_the_forwarded_https_host(): void
    {
        $request = Request::create('http://internal.test/comunigest/inicio', 'GET', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_HOST' => 'community.example.test',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        $this->app->instance('request', $request);
        URL::setRequest($request);

        $signedUrl = URL::temporarySignedRoute('livewire.upload-file', now()->addMinutes(5));
        $uploadRequest = Request::create($signedUrl, 'POST');

        $this->assertStringStartsWith('https://community.example.test/', $signedUrl);
        $this->assertTrue($uploadRequest->hasValidSignature());
        $this->assertStringContainsString("\$middleware->trustProxies(at: '*');", file_get_contents(base_path('bootstrap/app.php')));
    }

    public function test_community_header_reuses_the_portal_topbar_structure_and_glass_surface(): void
    {
        $view = file_get_contents(resource_path('views/livewire/community-portal.blade.php'));

        $this->assertStringContainsString('class="community-topbar"', $view);
        $this->assertStringContainsString('community-topbar__brand', $view);
        $this->assertStringContainsString('community-topbar__badge', $view);
        $this->assertStringContainsString('backdrop-filter: blur(28px) saturate(135%)', $view);
        $this->assertStringContainsString("asset('logos/logo_nova4.png')", $view);
    }
}
