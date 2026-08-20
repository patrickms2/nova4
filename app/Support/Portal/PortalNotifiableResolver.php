<?php

namespace App\Support\Portal;

use App\Models\Client;
use App\Models\Taxi\Usuario;
use App\Models\TaxistaPortalUser;
use App\Enums\PortalStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PortalNotifiableResolver
{
    public static function resolveClientByUserId(int $userId): ?Client
    {
        $user = DB::table('users')
            ->where('id', $userId)
            ->first(['id', 'email', 'nif']);

        if (!$user) {
            return null;
        }

        $taxistaId = DB::table('usuarios')
            ->whereIn('tipo_id', array_map('intval', (array)config('portal.auth.allowed_tipo_ids', [3, 4])))
            ->whereIn('status', array_map('intval', (array)config('portal.auth.enabled_statuses', [1])))
            ->where(function ($query) use ($user) {
                $query->where('user_id', (int)$user->id);

                if (!empty($user->email)) {
                    $query->orWhere('email', (string)$user->email);
                }

                if (!empty($user->oib)) {
                    $query->orWhereRaw("UPPER(COALESCE(cif, '')) = ?", [mb_strtoupper(trim((string)$user->oib))]);
                }
            })
            ->value('id');

        if (!$taxistaId) {
            return null;
        }

        return self::resolveClientByTaxistaId((int)$taxistaId);
    }

    public static function resolveClientByTaxistaId(int $taxistaId): ?Client
    {
        $taxista = Usuario::query()
            ->whereKey($taxistaId)
            ->first(['id', 'client_id', 'email', 'cif', 'nombre', 'password', 'tipo_id', 'status', 'user_id']);

        if (!$taxista) {
            return null;
        }

        $allowedTipoIds = config('portal.auth.allowed_tipo_ids', [3, 4]);
        $enabledStatuses = config('portal.auth.enabled_statuses', [1]);

        if (!in_array((int)$taxista->tipo_id, array_map('intval', (array)$allowedTipoIds), true)) {
            return null;
        }

        if (!in_array((int)$taxista->status, array_map('intval', (array)$enabledStatuses), true)) {
            return null;
        }

        $organisationId = null;

        if (!empty($taxista->user_id)) {
            $organisationId = DB::table('users')
                ->where('id', (int)$taxista->user_id)
                ->value('organisation_id');
        }

        if (!$organisationId && !empty($taxista->client_id)) {
            $organisationId = TaxistaPortalUser::withoutGlobalScopes()
                ->whereKey((int)$taxista->client_id)
                ->value('organisation_id');
        }

        if (!$organisationId && !empty($taxista->cif)) {
            $organisationId = TaxistaPortalUser::withoutGlobalScopes()
                ->whereRaw("UPPER(COALESCE(oib, '')) = ?", [mb_strtoupper(trim((string)$taxista->cif))])
                ->value('organisation_id');
        }

        if (!$organisationId) {
            return null;
        }

        $client = null;

        if (!empty($taxista->client_id)) {
            $client = TaxistaPortalUser::withoutGlobalScopes()
                ->whereKey((int)$taxista->client_id)
                ->where('organisation_id', (int)$organisationId)
                ->first();
        }

        if (!$client && !empty($taxista->email)) {
            $client = TaxistaPortalUser::withoutGlobalScopes()
                ->where('organisation_id', (int)$organisationId)
                ->where('email', $taxista->email)
                ->first();
        }

        if (!$client && !empty($taxista->cif)) {
            $client = TaxistaPortalUser::withoutGlobalScopes()
                ->where('organisation_id', (int)$organisationId)
                ->whereRaw("UPPER(COALESCE(oib, '')) = ?", [mb_strtoupper(trim((string)$taxista->cif))])
                ->first();
        }

        if (!$client) {
            $fallbackEmail = trim((string)$taxista->email);
            if ($fallbackEmail === '') {
                $fallbackEmail = 'taxista-' . $taxista->id . '-' . Str::lower(Str::random(4)) . '@portal.local';
            }

            $client = new TaxistaPortalUser();
            $client->fill([
                'first_name' => trim((string)($taxista->nombre ?? 'Taxista')),
                'last_name' => '',
                'email' => $fallbackEmail,
                'oib' => (string)($taxista->cif ?? ''),
                'organisation_id' => (int)$organisationId,
                'active' => 1,
                'portal_status' => PortalStatus::Active->value,
                'email_verified_at' => now(),
            ]);
        }

        if (!empty($taxista->password)) {
            $client->password = $taxista->password;
        }

        $client->organisation_id = (int)$organisationId;
        $client->portal_status = PortalStatus::Active->value;
        $client->active = 1;
        $client->save();

        if ((int)$taxista->client_id !== (int)$client->id) {
            $taxista->client_id = (int)$client->id;
            $taxista->save();
        }

        return $client;
    }
}
