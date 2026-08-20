<?php

namespace App\Filament\Support;

use App\Models\Taxi\Documento;
use App\Models\Taxi\Usuario;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use phpDocumentor\Reflection\PseudoTypes\LowercaseString;
use Spatie\PdfToText\Pdf;
use \Illuminate\Support\Facades\File;
use Illuminate\Support\Js;

abstract class baseresource extends Resource
{

    /**
     * Columna por la que se restringe al usuario autenticado.
     */
    protected static string $userIdColumn = 'usuario_id';
    protected static array $adminRoles = ['admin', 'superadmin', 'administrador'];
    protected static bool $isGloballySearchable = false;

    /**
     * Si false, no aplica la restricción por usuario en este Resource.
     */
    protected static function shouldRestrictToCurrentUser(): bool
    {
        if(auth()->user()->role_name == 'admin' || auth()->user()->role_name == 'superadmin')
            return true;
        else
            return false;
    }

    public static function getTableDefaultAction(): ?string
    {
        return 'edit';
    }
    public static function areGroupsCollapsedByDefault(): bool
    {
        return true;
    }
    public static function comprueba(string $originalName){
        $created = 0;
        $logRows = [];
        // Cabecera CSV
        ds($originalName);
        $logRows[] = ['filename','cif','usuario_id','tipo','year','mes','issues'];
        $logDir = 'app/public/import_logs';
        if (!Storage::exists($logDir)) {
            Storage::makeDirectory($logDir);
        }
        $dir = storage_path("app/public/documentos/");
        $csvPath = $logDir . '/import_' . date('Ymd_His') . '.csv';
        $file = \Illuminate\Support\Facades\File::name($dir.'/'.$originalName);

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $file = pathinfo($originalName, PATHINFO_FILENAME);

        if (strtolower($ext) !== 'pdf') return false;;

        $pdfFile = $originalName;
        $pdfName = $file;

        /*ds($pdfFile);
        ds($pdfName);*/

        $issues  = [];

        // --- NIF en nombre ---
        $nif = null;
        if (preg_match('/\b(\d{8})([A-Za-z])\b/', $pdfName, $matches)) {
            $numero = (int) $matches[1];
            $letra  = strtoupper($matches[2]);
            $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
            $letraCorrecta = $letras[$numero % 23];
            if ($letra === $letraCorrecta) {
                $nif = $numero . $letra;
            }
        }

        // --- Si no hay NIF en nombre: leer contenido PDF ---
        $textoPdf = null;
        if (!$nif) {
            try {
                $textoPdf = Pdf::getText($pdfFile);
                if (preg_match('/\b(\d{8})([A-Za-z])\b/', $textoPdf, $m2)) {
                    $numero = (int) $m2[1];
                    $letra  = strtoupper($m2[2]);
                    $letras = "TRWAGMYFPDXBNJZSQVHLCKE";
                    $letraCorrecta = $letras[$numero % 23];
                    if ($letra === $letraCorrecta) {
                        $nif = $numero . $letra;
                    }
                }
            } catch (\Throwable $e) {
                $issues[] = 'Contenido no legible';
            }
        }
        if (!$nif) $issues[] = 'NIF no detectado';

        // --- Usuario por NIF ---
        $usuarioId = null;
        if ($nif) {
            $usuario = Usuario::where('cif', $nif)->first();
            if ($usuario) {
                $usuarioId = $usuario->id;
            } else {
                $issues[] = 'Usuario no encontrado para NIF';
            }
        }

        // --- Año/Mes por nombre ---
        $year = null; $mes = null;
        if (preg_match('/\b(20\d{2})[-_](0[1-9]|1[0-2])\b/', $pdfName, $d)) {
            $year = $d[1];
            $mes  = $d[2];
        }

        // --- Si no hay fecha en nombre: buscar en contenido ---
        if (!$year || !$mes) {
            if ($textoPdf === null) {
                try { $textoPdf = Pdf::getText($pdfFile); } catch (\Throwable $e) {}
            }
            if (!$year && $textoPdf && preg_match('/\b(20\d{2})\b/', $textoPdf, $y)) {
                $year = $y[1];
            }
            if (!$mes && $textoPdf) {
                $mesesMap = [
                    'enero'=>'01','febrero'=>'02','marzo'=>'03','abril'=>'04','mayo'=>'05','junio'=>'06',
                    'julio'=>'07','agosto'=>'08','septiembre'=>'09','octubre'=>'10','noviembre'=>'11','diciembre'=>'12'
                ];
                if (preg_match('/\b(0[1-9]|1[0-2])\b/', $textoPdf, $mN)) {
                    $mes = $mN[1];
                } elseif (preg_match('/\b('.implode('|', array_keys($mesesMap)).')\b/i', $textoPdf, $mT)) {
                    $mes = $mesesMap[strtolower($mT[1])];
                }
            }
        }
        // Garantizar valores
        $year = $year ?? date('Y');
        $mes  = $mes  ?? date('m');

        // --- Tipo ---
        $tipo = null;
        $tipos = [
            "factura" => "Factura",
            "recibo" => "Recibo",
            "contrato" => "Contrato",
            "presupuesto" => "Presupuesto",
            "borrador" => "Borrador",
            "certificado" => "Certificado",
            "certificado_de_pago" => "Certificado de Pago",
            "igic" => "Igic",
            "renta" => "Renta",
            "model111" => "111",
            "taxi" => "Taxi",
            "nomina" => "Nomina",
            "dni" => "DNI",
            "nif" => "NIF",
            "cif" => "CIF",
        ];
        foreach ($tipos as $keyword => $label) {
            if (stripos($pdfName, $keyword) !== false) {
                $tipo = $label;
                break;
            }
        }
        if (!$tipo){
            $tipo = $originalName;
            $issues[] = 'Tipo no detectado';
        }

        $rutaPdf = Storage::disk('documentos')->putFile("documentos", new \Illuminate\Http\File($pdfFile));
        Documento::create([
            'file_name'  => $pdfName,
            'file_path'  => $pdfFile,
            'nif'        => $nif,
            'usuario_id' => $usuarioId,
            'tipo'       => $tipo,
            'year'       => $year,
            'mes'        => $mes,
            'log'        => $csvPath,
        ]);
        $logRows[] = [$pdfName,$nif,$usuarioId,$tipo,$year,$mes,implode(', ',$issues)];
        return $logRows;
        /*ds($logRows);*/
    }


/*protected static function shouldRestrictToCurrentUser(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return true;
        }

        // Admins no se restringen
        return ! static::userHasAdminRole($user);
    }*/

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // Sin sesión -> no mostrar resultados
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Si este Resource no debe restringir, devolvemos el query tal cual
        if (! static::shouldRestrictToCurrentUser()) {
            return $query;
        }

        // Usuario normal -> solo sus registros
        return $query->where(static::getUserIdColumn(), $user->getAuthIdentifier());
    }

    protected static function userHasAdminRole(mixed $user): bool
    {
        try {
            if (is_object($user)) {

                /*if (method_exists($user, 'hasAnyRole')) {
                    return $user->hasAnyRole(static::$adminRoles);
                }

                if (method_exists($user, 'roles')) {
                    $role = $user->roles()->first();
                    $nombre = strtolower($role->name ?? $role->nombre ?? $role->display_name ?? '');
                    return in_array($nombre, static::$adminRoles, true);
                }*/
                //dd($user);
                // Fallback: propiedad simple
                $nombre = strtolower($user->role_name ?? '');
                if ($nombre !== '') {
                    return in_array($nombre, static::$adminRoles, true);
                }

            }
        } catch (\Throwable) {
            // Ignorar y considerar no-admin
        }

        return false;
    }

    protected static function getUserIdColumn(): string
    {
        return static::$userIdColumn;
    }





    public static function getNavigationBadge(): ?string
    {
        $userId = auth()->id();
        if (! $userId) {
            return null;
        }

        $key = static::navigationBadgeCacheKey($userId);

        $count = cache()->remember($key, static::navigationBadgeCacheTtl(), function () {
            // Reutiliza el MISMO query que usa la tabla
            return static::getEloquentQuery()->count();
        });

        return (string) $count;
    }


    /**
     * Ajusta esta TTL si quieres badge más “fresco” o más cacheado.
     */
    protected static function navigationBadgeCacheTtl(): \DateTimeInterface|\DateInterval|int
    {
        return now()->addMinutes(2);
    }

    protected static function navigationBadgeCacheKey(int $userId): string
    {
        return 'nav_badge:' . static::class . ':' . $userId;
    }

}
