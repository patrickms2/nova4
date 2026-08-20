<?php

declare(strict_types=1);

namespace App\Models\Taxi;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

final class Taxi extends Model
{
    protected $table = 'taxis';

    protected $fillable = [
        'matricula', 'modelo', 'anio', 'usuario_id', 'tipotaxi', 'licencia', 'estado', 'municipio_id', 'extras', 'chofer_id', 'observaciones',
    ];

    protected $casts = [
        'tipotaxi' => 'array',
        'extras' => 'array',
    ];

    protected $appends = ['tipo_taxi'];

    public function getTipoTaxiAttribute($value)
    {

        if (is_null($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? implode(',', $decoded) : [];
    }

    public function getExtraAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? implode($decoded) : [];
    }

    public function servicios()
    {
        return $this->belongsTo(Servicio::class, 'usuario_id');
    }

    public function taxista()
    {
        return $this->belongsTo(Taxista::class, 'usuario_id');
    }

    public function taxistas()
    {
        return $this->belongsToMany(Taxista::class, 'taxis_conductores', 'taxista_id', 'conductor_id');

    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'usuario_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function extras()
    {
        return $this->hasMany(Extra::class, 'id');
    }

    public function conductorTaxista()
    {
        return $this->belongsToMany(Conductor::class, 'taxis_conductores', 'taxista_id', 'conductor_id');
    }

    public function conductor()
    {
        return $this->belongsTo(Conductor::class, 'chofer_id');
    }

    public function tipotaxi()
    {
        return $this->hasMany(TipoTaxi::class, 'id');
    }

    /**
     * @return array{processed: int, linked: int, unmatched: int, duplicated_name_matches: int}
     */
    public static function linkTaxistasFromRemarks(bool $onlyMissingTaxistaId = true): array
    {
        if (! Schema::hasColumn('taxis', 'remarks')) {
            return [
                'processed' => 0,
                'linked' => 0,
                'unmatched' => 0,
                'duplicated_name_matches' => 0,
            ];
        }

        $taxistasByName = Taxista::query()
            ->select(['id', 'nombre'])
            ->whereNotNull('nombre')
            ->get()
            ->mapWithKeys(function (Taxista $taxista): array {
                $key = self::normalizeOwnerName((string) ($taxista->nombre ?? ''));

                if ($key === '') {
                    return [];
                }

                return [$key => (int) $taxista->id];
            });

        $query = self::query()
            ->select(['id', 'remarks', 'taxista_id', 'usuario_id'])
            ->whereNotNull('remarks');

        if ($onlyMissingTaxistaId && Schema::hasColumn('taxis', 'taxista_id')) {
            $query->whereNull('taxista_id');
        }

        $processed = 0;
        $linked = 0;
        $unmatched = 0;
        $duplicatedNameMatches = 0;

        foreach ($query->cursor() as $taxi) {
            $processed++;

            $remarkName = self::normalizeOwnerName((string) ($taxi->remarks ?? ''));

            if ($remarkName === '') {
                $unmatched++;

                continue;
            }

            $taxistaId = $taxistasByName->get($remarkName);

            if (! $taxistaId) {
                $candidateIds = $taxistasByName
                    ->filter(fn (int $id, string $name): bool => str_contains($remarkName, $name) || str_contains($name, $remarkName))
                    ->values()
                    ->unique();

                if ($candidateIds->count() !== 1) {
                    if ($candidateIds->count() > 1) {
                        $duplicatedNameMatches++;
                    }

                    $unmatched++;

                    continue;
                }

                $taxistaId = (int) $candidateIds->first();
            }

            $updates = [];

            if (Schema::hasColumn('taxis', 'taxista_id') && (int) ($taxi->taxista_id ?? 0) !== (int) $taxistaId) {
                $updates['taxista_id'] = $taxistaId;
            }

            if (Schema::hasColumn('taxis', 'usuario_id') && ! filled($taxi->usuario_id)) {
                $updates['usuario_id'] = $taxistaId;
            }

            if ($updates === []) {
                continue;
            }

            self::query()->whereKey($taxi->id)->update($updates);
            $linked++;
        }

        return [
            'processed' => $processed,
            'linked' => $linked,
            'unmatched' => $unmatched,
            'duplicated_name_matches' => $duplicatedNameMatches,
        ];
    }

    private static function normalizeOwnerName(string $value): string
    {
        $normalized = Str::upper(Str::ascii($value));
        $normalized = preg_replace('/\b(LM|M)\s*[\.\-]?\s*\d{1,4}\b/u', ' ', $normalized) ?: $normalized;
        $normalized = preg_replace('/[^A-Z\s]/u', ' ', $normalized) ?: $normalized;
        $normalized = trim((string) preg_replace('/\s+/', ' ', $normalized));

        return $normalized;
    }
}
