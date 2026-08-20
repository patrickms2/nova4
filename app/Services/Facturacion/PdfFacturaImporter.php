<?php

namespace App\Services\Facturacion;

use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Factura;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PdfFacturaImporter
{
    public function import(UploadedFile $file): array
    {
        $text = $this->extractText($file);
        Log::debug('PDF import raw text', ['text' => $text]);

        $data = $this->parseText($text);
        Log::debug('PDF import parsed data', ['data' => $data]);

        if (empty($data['cliente']['nombre'])) {
            throw new \InvalidArgumentException('No se pudo detectar el cliente en el PDF.');
        }

        return DB::transaction(function () use ($data): array {
            $cliente = $this->findOrCreateCliente($data['cliente']);
            $factura = $this->createFactura($cliente, $data);
            return [
                'factura_id' => $factura->id,
                'codfactura' => $factura->codfactura,
                'cliente_id' => $cliente->id,
                'cliente_nombre' => $cliente->nombretotal,
                'lineas' => count($data['lineas']),
            ];
        });
    }

    private function extractText(UploadedFile $file): string
    {
        $path = $file->getRealPath();

        $pdftotext = config('facturacion.pdftotext_path', '/opt/homebrew/bin/pdftotext');
        $process = new Process([$pdftotext, '-layout', $path, '-']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    private function parseText(string $text): array
    {
        $lines = array_map('trim', explode("\n", $text));
        $lines = array_values(array_filter($lines, fn ($l) => $l !== ''));

        $data = [
            'cliente' => [],
            'factura' => [],
            'lineas' => [],
        ];

        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];

            /*if (str_starts_with($line, 'Nº:')) {
                $data['factura']['numero'] = trim(str_replace('Nº:', '', $line));
            }*/

            if (str_starts_with($line, 'Fecha emisión:')) {
                $data['factura']['fecha'] = trim(str_replace('Fecha emisión:', '', $line));
            }

            if (str_starts_with($line, 'Cliente:')) {
                $clienteLine = trim(str_replace('Cliente:', '', $line));

                // Extrae el CIF si va al final de la línea del cliente y lo quita del nombre.
                if (preg_match('/:\s*([A-Z0-9]+)\s*$/', $clienteLine, $cifMatches)) {
                    $data['cliente']['cif'] = $cifMatches[1];
                    $clienteLine = trim(preg_replace('/:\s*'.$cifMatches[1].'\s*$/', '', $clienteLine));
                    Log::debug('PDF import cliente CIF extraído', ['cif' => $cifMatches[1], 'nombre_limpio' => $clienteLine]);
                }

                $data['cliente']['nombre'] = $clienteLine;

                // Une línea de continuación (p. ej. "SC") si la siguiente línea no es otra etiqueta.
                if ($i + 1 < $count) {
                    $next = $lines[$i + 1];
                    if (! str_contains($next, ':') && ! preg_match('/^\d{2}\.\d{2}\.\d{4}/', $next)) {
                        $data['cliente']['nombre'] = $clienteLine.' '.$next;
                        $i++;
                    }
                }
            }

            if (str_starts_with($line, 'Dirección:')) {
                $data['cliente']['direccion'] = trim(str_replace('Dirección:', '', $line));
            }

            if (str_starts_with($line, ':')) {
                $cif = trim(str_replace(':', '', $line));
                if (preg_match('/^[A-Z0-9]+$/', $cif)) {
                    $data['cliente']['cif'] = $cif;
                }
            }

            if (preg_match('/^(\d{2}\.\d{2}\.\d{4})\s+(.+?)\s+(\d+)\s+(\d+)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)\s+([\d.,]+)\s*$/', $line, $matches)) {
                $descripcion = trim($matches[2]);

                // Une línea de continuación si la siguiente línea no es numérica.
                if ($i + 1 < $count) {
                    $next = $lines[$i + 1];
                    if (! preg_match('/^\d{2}\.\d{2}\.\d{4}/', $next) && ! preg_match('/B\.\s*Exenta|B\.\s*Imponible|Retenciones|IGIC|Importe:/', $next)) {
                        $descripcion .= ' / '.trim($next);
                        $i++;
                    }
                }

                $data['lineas'][] = [
                    'fecha' => $matches[1],
                    'descripcion' => $descripcion,
                    'cantidad' => $this->parseNumber($matches[3]),
                    'unidad' => $matches[4],
                    'precio' => $this->parseNumber($matches[5]),
                    'descuento' => $this->parseNumber($matches[6]),
                    'impuesto' => $this->parseNumber($matches[7]),
                    'retenciones' => $this->parseNumber($matches[8]),
                    'importe' => $this->parseNumber($matches[9]),
                ];
            }

            if (preg_match('/B\.\s*Imponible\s*(\d+)%\s*:\s*([\d.,]+)/', $line, $matches)) {
                $data['factura']['impuesto_tipo'] = $this->parseNumber($matches[1]);
                $data['factura']['base_imponible'] = $this->parseNumber($matches[2]);
            }

            if (preg_match('/Retenciones:\s*([\d.,]+)/', $line, $matches)) {
                $data['factura']['retenciones'] = $this->parseNumber($matches[1]);
            }

            if (preg_match('/IGIC\s*(\d+)%\s*:\s*([\d.,]+)/', $line, $matches)) {
                $data['factura']['impuesto_tipo'] = $this->parseNumber($matches[1]);
                $data['factura']['impuesto'] = $this->parseNumber($matches[2]);
            }

            if (preg_match('/Importe:\s*([\d.,]+)\s*€/', $line, $matches)) {
                $data['factura']['importe'] = $this->parseNumber($matches[1]);
            }

            $i++;
        }

        if (empty($data['lineas']) && ! empty($data['factura']['base_imponible'])) {
            $data['lineas'][] = [
                'fecha' => $data['factura']['fecha'] ?? now()->format('d.m.Y'),
                'descripcion' => 'Concepto importado',
                'cantidad' => 1,
                'unidad' => '1',
                'precio' => $data['factura']['base_imponible'],
                'descuento' => 0,
                'impuesto' => $data['factura']['impuesto_tipo'] ?? 7,
                'retenciones' => 15,
                'importe' => $data['factura']['importe'] ?? $data['factura']['base_imponible'],
            ];
        }

        return $data;
    }

    private function findOrCreateCliente(array $data): Cliente
    {
        $nombre = trim($data['nombre'] ?? '');
        if ($nombre === '') {
            throw new \InvalidArgumentException('Falta el nombre del cliente.');
        }

        // Limpia el nombre si el CIF quedó concatenado en la misma línea.
        $cif = $data['cif'] ?? null;
        if ($cif !== null && str_ends_with($nombre, $cif)) {
            $nombre = trim(substr($nombre, 0, -strlen($cif)));
        }
        $nombre = trim(rtrim($nombre, ':'));
        $nombre = preg_replace('/\s+/', ' ', $nombre);

        $query = Cliente::query()->where('nombretotal', $nombre);
        if (filled($cif)) {
            $query->orWhere('dni', $cif);
        }
        $cliente = $query->first();

        Log::debug('PDF import cliente buscado', ['nombre' => $nombre, 'cif' => $cif, 'encontrado' => $cliente?->nombretotal]);

        if ($cliente) {
            return $cliente;
        }

        $parts = explode(' ', $nombre, 2);

        return Cliente::create([
            'codcliente' => $this->nextCodcliente(),
            'nombretotal' => $nombre,
            'nombre' => $parts[0],
            'apellido' => $parts[1] ?? '',
            'dni' => $data['cif'] ?? null,
            'domicilio' => $data['direccion'] ?? null,
            'telefono' => null,
            'email' => null,
            'fechaalta' => now(),
            'recurrencia_activa' => false,
        ]);
    }

    private function createFactura(Cliente $cliente, array $data): Factura
    {
        $fecha = filled($data['factura']['fecha'] ?? null)
            ? Carbon::createFromFormat('d.m.Y', $data['factura']['fecha'])
            : now();

        $base = (float) ($data['factura']['base_imponible'] ?? 0);
        $igic = (float) ($data['factura']['impuesto'] ?? 0);
        $ret = (float) ($data['factura']['retenciones'] ?? 0);
        $importe = (float) ($data['factura']['importe'] ?? ($base + $igic - $ret));

        $factura = new Factura;
        $factura->cliente_id = $cliente->id;
        $factura->codcliente = $cliente->codcliente;
        $factura->cliente_nombre = $cliente->nombretotal;
        $factura->cliente_cif = $cliente->dni;
        $factura->fechaemitido = $fecha;
        $factura->baseimponible = $base;
        $factura->baseexenta = 0;
        $factura->impuesto = $igic;
        $factura->retenciones = $ret;
        $factura->importe = $importe;
        $factura->observaciones = 'Importado desde PDF. Nº original: '.($data['factura']['numero'] ?? 'desconocido');
        $factura->save();

        foreach ($data['lineas'] as $linea) {
            $concepto = $this->findOrCreateConcepto($cliente, $linea);
            $factura->registros()->create([
                'concepto_id' => $concepto->id,
                'descripcion' => $linea['descripcion'],
                'cantidad' => $linea['cantidad'],
                'unidad' => $linea['unidad'],
                'precio' => $linea['precio'],
                'descuento' => $linea['descuento'],
                'impuesto' => $linea['impuesto'],
                'retenciones' => $linea['retenciones'],
                'valorimpuesto' => round($linea['precio'] * $linea['cantidad'] * ($linea['impuesto'] / 100), 2),
                'valorretenciones' => round($linea['precio'] * $linea['cantidad'] * ($linea['retenciones'] / 100), 2),
                'importe' => $linea['importe'],
                'fecha' => $fecha,
            ]);
        }

        return $factura;
    }

    private function findOrCreateConcepto(Cliente $cliente, array $linea): Concepto
    {
        $descripcion = $linea['descripcion'];
        $concepto = Concepto::query()
            ->where('cliente_id', $cliente->id)
            ->first();

        if ($concepto) {
            return $concepto;
        }

        return Concepto::create([
            'codconcepto' => $this->nextCodconcepto(),
            'cliente_id' => $cliente->id,
            'concepto' => $descripcion,
            'unidad' => $linea['unidad'],
            'precio' => $linea['precio'],
            'descuento' => $linea['descuento'],
            'impuesto' => $linea['impuesto'],
            'retenciones' => $linea['retenciones'],
            'recurrente' => false,
        ]);
    }

    private function parseNumber(string $value): float
    {
        //$value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (float) $value;
    }

    private function nextCodcliente(): int
    {
        return (int) (Cliente::max('codcliente') ?? 0) + 1;
    }

    private function nextCodconcepto(): int
    {
        return (int) (Concepto::max('codconcepto') ?? 0) + 1;
    }
}
