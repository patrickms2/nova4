<?php

namespace App\Console\Commands;

use App\Enums\DomoticsEventType;
use App\Jobs\OpenAccessPoint;
use App\Models\AccessGrant;
use App\Models\AccessPoint;
use App\Models\Credential;
use App\Models\DomoticsEvent;
use Illuminate\Console\Command;

class ValidateAccessPin extends Command
{
    protected $signature = 'app:validate-access-pin {pin} {accessPoint}';

    protected $description = 'Valida un PIN para un punto de acceso y abre si es válido';

    public function handle(): int
    {
        $pin = $this->argument('pin');
        $accessPointId = (int) $this->argument('accessPoint');

        $accessPoint = AccessPoint::find($accessPointId);

        if (! $accessPoint) {
            $this->error('Punto de acceso no encontrado');

            return self::FAILURE;
        }

        $grant = AccessGrant::where('property_id', $accessPoint->property_id)
            ->where('pin', $pin)
            ->where('is_active', true)
            ->first();

        $grant ??= AccessGrant::query()
            ->where('property_id', $accessPoint->property_id)
            ->where('is_active', true)
            ->whereHas('credentials', fn ($query) => $query->where('type', 'pin')->where('status', 'active'))
            ->with('credentials')
            ->get()
            ->first(fn (AccessGrant $candidate): bool => $candidate->credentials->contains(fn (Credential $credential): bool => hash_equals((string) $credential->secret, (string) $pin)));

        if (! $grant || ! $grant->isValidForAccessPoint($accessPoint)) {
            DomoticsEvent::create([
                'property_id' => $accessPoint->property_id,
                'access_point_id' => $accessPoint->id,
                'event_type' => DomoticsEventType::AccessDenied,
                'payload' => ['credential_type' => 'pin'],
                'created_at' => now(),
            ]);

            $this->error('PIN inválido o sin acceso a este punto');

            return self::FAILURE;
        }

        dispatch_sync(new OpenAccessPoint($accessPoint, null, $grant->id));

        $this->info('Acceso concedido');

        return self::SUCCESS;
    }
}
