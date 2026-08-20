<?php

namespace App\Support\Portal;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PortalTaxistaContext
{
    public static function meetingCreatorUserId(): ?int
    {
        $taxistaId = self::taxistaId();

        if ($taxistaId) {
            $taxista = DB::table('users')
                ->where('id', $taxistaId)
                ->first(['email', 'nif']);

            if ($taxista && (filled($taxista->email ?? null) || filled($taxista->nif ?? null))) {
                $userId = DB::table('users')
                    ->when(
                        filled($taxista->email ?? null),
                        fn($query) => $query->where('email', $taxista->email),
                    )
                    ->when(
                        filled($taxista->nif ?? null),
                        fn($query) => $query->orWhere('nif', $taxista->nif),
                    )
                    ->value('id');

                if ($userId) {
                    return (int)$userId;
                }
            }
        }

        $authId = self::authId();

        if ($authId && DB::table('users')->where('id', $authId)->exists()) {
            return $authId;
        }

        return null;
    }

    public static function clientId(): ?int
    {
        $authId = self::authId();

        if (!$authId) {
            return null;
        }

        $selectedTaxistaId = self::selectedTaxistaId();

        if (!$selectedTaxistaId) {
            return $authId;
        }

        $clientId = DB::table('users')
            ->where('id', $selectedTaxistaId)
            ->value('client_id');

        return $clientId ? (int)$clientId : $authId;
    }

    public static function taxistaId(): ?int
    {
        $selectedTaxistaId = self::selectedTaxistaId();

        if ($selectedTaxistaId) {
            return $selectedTaxistaId;
        }

        $authId = self::authId();

        if (!$authId) {
            return null;
        }

        if (Schema::hasTable('booking_departments') && DB::table('booking_departments')->where('created_by', $authId)->exists()) {
            $usuarioIdFromClient = DB::table('booking_departments')
                ->where('created_by', $authId)
                ->value('created_by');

            if ($usuarioIdFromClient) {
                return (int)$usuarioIdFromClient;
            }
        }

        $taxistaId = DB::table('users')
            ->where('id', $authId)
            ->where('role', 'taxista')
            ->value('id');

        if ($taxistaId) {
            return (int)$taxistaId;
        }

        $directTaxista = DB::table('users')
            ->where('id', $authId)
            ->where('role', 'taxista')
            ->value('id');

        return $directTaxista ? (int)$directTaxista : null;
    }

    private static function authId(): ?int
    {
        $id = Auth::id();

        return $id ? (int)$id : null;
    }

    private static function selectedTaxistaId(): ?int
    {
        if (!config('portal.verification_taxista_selector_enabled', false)) {
            return null;
        }

        $taxistaId = (int)session('portal.verification_taxista_id');

        if (!$taxistaId) {
            return null;
        }

        $exists = DB::table('users')
            ->where('id', $taxistaId)
            ->where('role', 'taxista')
            ->exists();

        if (!$exists) {
            session()->forget('portal.verification_taxista_id');

            return null;
        }

        return $taxistaId;
    }
}
