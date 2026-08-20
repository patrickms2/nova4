<?php

namespace App\Services;

use App\Models\Taxi\Pago;
use Illuminate\Support\Facades\DB;

class PagoReferenciaService
{
    /**
     * Genera una referencia usando el próximo AUTO_INCREMENT de MySQL (si está disponible).
     * Útil cuando necesitas la referencia ANTES de insertar el registro.
     * Nota: Es una estimación en escenarios altamente concurrentes (puede cambiar si otra inserción ocurre antes).
     */
    public static function generateUsingNextAutoIncrement(): string
    {
        $next = self::nextAutoIncrement('taxis_pagos');

        // Fallback si no se pudo leer el AUTO_INCREMENT por alguna razón:
        if ($next === null) {
            $next = (int) (Pago::max('id') ?? 0) + 1;
        }

        return self::buildReferenceFromId($next);
    }

    /**
     * Genera la referencia usando un id concreto (RECOMENDADO: úsalo después de insertar el Pago).
     */
    public static function buildReferenceFromId(int $id): string
    {
        // Mantiene tu lógica original: ymdHis + 'Id' + id, y recorta a 12 chars.
        // Ajusta si prefieres solo dígitos.
        $base = now()->format('ymdHis') . 'Id' . $id;

        return substr($base, -12, 12);
    }

    /**
     * Para llamar inmediatamente después de crear el Pago: calcula y guarda la referencia si está vacía.
     */
    public static function setReferenciaIfEmpty(Pago $pago): void
    {
        if (empty($pago->referencia)) {
            $pago->referencia = self::buildReferenceFromId((int) $pago->id);
            $pago->save();
        }
    }

    /**
     * Obtiene el próximo AUTO_INCREMENT de una tabla MySQL.
     */
    private static function nextAutoIncrement(string $table): ?int
    {
        $row = DB::selectOne("
            SELECT AUTO_INCREMENT AS next_id
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
        ", [$table]);

        if (!$row || !isset($row->next_id)) {
            return null;
        }

        return (int) $row->next_id;
    }
}
