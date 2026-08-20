<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Contracts\VeriFactuBreakdown;
use App\Contracts\VeriFactuInvoice;

class Factura extends Model implements VeriFactuInvoice
{
    use HasFactory;

    protected $fillable = [
        'codfactura',
        'codeempresa',
        'codcliente',
        'cliente_id',
        'empresa_id',
        'remesa_id',
        'cliente_nombre',
        'cliente_cif',
        'notas',
        'cliente_direccion',
        'cliente_telefono',
        'fechaemitido',
        'baseimponible',
        'baseexenta',
        'impuesto',
        'retenciones',
        'importe',
        'pagada',
        'rectificativa',
        'factura_original_id',
        'observaciones',
        'meta',
        'verifactu_status',
        'verifactu_hash',
        'verifactu_previous_hash',
        'verifactu_response_code',
        'verifactu_response_message',
        'verifactu_registration_date',
        'verifactu_csv',
        'verifactu_qr_url',
        'verifactu_sent_at',
        'verifactu_request_xml',
        'verifactu_response_xml',
    ];

    protected $casts = [
        'fechaemitido' => 'date',
        'pagada' => 'boolean',
        'rectificativa' => 'boolean',
        'meta' => 'array',
        'verifactu_registration_date' => 'datetime',
        'verifactu_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Factura $factura): void {


            $factura->codfactura = self::nextNumero();
        });
    }

    public static function suggestNumero(?string $codfactura = null): string
    {
        if (filled($codfactura)) {
            return $codfactura;
        }

        $contador = ContadorFactura::query()
            ->where('ano', now()->year)
            ->orderByDesc('id')
            ->first();

        $next = $contador ? $contador->contador + 1 : 1;

        return str_pad((string) $next, 5, '0', STR_PAD_LEFT).'_'.now()->format('Y');
    }

    private static function nextNumero(): string
    {
        return DB::transaction(function (): string {
            $year = now()->year;
            $contador = ContadorFactura::query()
                ->where('ano', $year)
                ->lockForUpdate()
                ->first();

            if (! $contador) {
                $contador = ContadorFactura::query()->create([
                    'ano' => $year,
                    'contador' => 0,
                ]);
            }

            $contador->increment('contador');

            return str_pad((string) $contador->contador, 5, '0', STR_PAD_LEFT).'_'.$year;
        });
    }

    public function registros()
    {
        return $this->hasMany(RegistroFactura::class, 'factura_id');
    }

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'factura_id');
    }
    public function remesa()
    {
        return $this->belongsTo(Remesa::class, 'remesa_id');
    }
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function novaBusiness()
    {
        return $this->hasOneThrough(
            NovaBusiness::class,
            Cliente::class,
            'codcliente',
            'contact_email',
            'cliente_id',
            'email',
        );
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function rectificativaDe()
    {
        return $this->belongsTo(Factura::class, 'factura_original_id');
    }

    public function rectificativas()
    {
        return $this->hasMany(Factura::class, 'factura_original_id');
    }

    public function createRectificativa(): self
    {
        return DB::transaction(function (): self {
            $copy = $this->replicate();
            $copy->codcliente = $this->codcliente;
            $copy->cliente_id = $this->cliente_id;
            $copy->empresa_id = $this->empresa_id;
            $copy->remesa_id = null;
            $copy->pagada = false;
            $copy->rectificativa = true;
            $copy->factura_original_id = $this->id;
            $copy->observaciones = ($this->observaciones ? $this->observaciones.'\n' : '').'Rectificativa de factura '.$this->codfactura;
            $copy->baseimponible = -1 * abs($this->baseimponible ?? 0);
            $copy->impuesto = -1 * abs($this->impuesto ?? 0);
            $copy->retenciones = -1 * abs($this->retenciones ?? 0);
            $copy->importe = -1 * abs($this->importe ?? 0);
            $copy->save();

            foreach ($this->registros as $line) {
                $copy->registros()->create([
                    'concepto_id' => $line->concepto_id,
                    'unidad' => $line->unidad,
                    'descripcion' => $line->descripcion,
                    'cantidad' => $line->cantidad,
                    'precio' => -1 * abs($line->precio ?? 0),
                    'descuento' => $line->descuento,
                    'valorimpuesto' => -1 * abs($line->valorimpuesto ?? 0),
                    'valorretenciones' => -1 * abs($line->valorretenciones ?? 0),
                    'importe' => -1 * abs($line->importe ?? 0),
                    'fecha' => $line->fecha,
                ]);
            }

            return $copy;
        });
    }

    public function totalBase(): float
    {
        return (float) ($this->baseimponible ?? 0);
    }

    public function totalImpuestos(): float
    {
        return (float) ($this->impuesto ?? 0);
    }

    public function totalFinal(): float
    {
        return (float) ($this->importe ?? 0);
    }

    public function duplicate(): self
    {
        $copy = $this->replicate();
        $copy->codfactura = $this->codfactura.'-COPY-'.now()->format('YmdHis');
        $copy->pagada = false;
        $copy->push();

        foreach ($this->registros as $line) {
            $copy->registros()->create($line->only([
                'concepto_id',
                'unidad',
                'descripcion',
                'cantidad',
                'precio',
                'descuento',
                'valorimpuesto',
                'valorretenciones',
                'importe',
                'fecha',
            ]));
        }

        return $copy;
    }

    // VeriFactuInvoice contract implementation

    public function getInvoiceNumber(): string
    {
        return (string) $this->codfactura;
    }

    public function getIssueDate(): Carbon
    {
        return Carbon::parse($this->fechaemitido);
    }

    public function getInvoiceType(): string
    {
        return $this->rectificativa ? 'R1' : 'F1';
    }

    public function getTotalAmount(): float
    {
        return (float) $this->importe;
    }

    public function getTaxAmount(): float
    {
        return (float) $this->impuesto;
    }

    public function getCustomerName(): string
    {
        return (string) ($this->cliente_nombre ?? optional($this->cliente)->nombretotal ?? 'Cliente');
    }

    public function getCustomerTaxId(): ?string
    {
        return $this->cliente_cif ?? optional($this->cliente)->dni;
    }

    public function getBreakdowns(): Collection
    {
        return $this->registros->groupBy('impuesto')->map(function ($group) {
            $base = (float) $group->sum(function ($line) {
                return $line->cantidad * $line->precio * (1 - $line->descuento / 100);
            });
            $tax = (float) $group->sum('valorimpuesto');
            $rate = (float) $group->first()->impuesto;

            $regimeType = $this->resolveRegimeType($rate);
            $operationType = $regimeType === '08' ? 'N2' : 'S1';

            return new class($base, $tax, $rate, $regimeType, $operationType) implements VeriFactuBreakdown {
                public function __construct(
                    private float $base,
                    private float $tax,
                    private float $rate,
                    private string $regimeType,
                    private string $operationType,
                ) {}

                public function getRegimeType(): string
                {
                    return $this->regimeType;
                }

                public function getOperationType(): string
                {
                    return $this->operationType;
                }

                public function getTaxRate(): float
                {
                    return $this->rate;
                }

                public function getBaseAmount(): float
                {
                    return $this->base;
                }

                public function getTaxAmount(): float
                {
                    return $this->tax;
                }
            };
        })->values();
    }

    private function resolveRegimeType(float $rate): string
    {
        $default = config('verifactu.regime_type', '08');
        $igicRates = config('verifactu.igic_rates', [0.00, 3.00, 7.00, 9.50, 15.00, 20.00]);

        foreach ($igicRates as $igicRate) {
            if (abs($rate - (float) $igicRate) < 0.001) {
                return '08';
            }
        }

        return $default;
    }

    public function getRecipients(): Collection
    {
        if (! $this->cliente) {
            return collect();
        }

        return collect([$this->cliente]);
    }

    public function getPreviousHash(): ?string
    {
        return $this->verifactu_previous_hash ?: null;
    }

    public function previousVeriFactu(): ?self
    {
        return self::query()
            ->where('empresa_id', $this->empresa_id)
            ->where('id', '<>', $this->id)
            ->where('verifactu_status', 'accepted')
            ->whereNotNull('verifactu_hash')
            ->orderByDesc('fechaemitido')
            ->orderByDesc('id')
            ->first();
    }

    public function isVeriFactuSent(): bool
    {
        return in_array($this->verifactu_status, ['sent', 'accepted'], true);
    }

    public function getOperationDescription(): string
    {
        return $this->notas ?: $this->observaciones ?: 'Factura';
    }

    public function getOperationDate(): ?Carbon
    {
        return Carbon::parse($this->fechaemitido);
    }

    public function getTaxPeriod(): ?string
    {
        return $this->fechaemitido?->format('m');
    }

    public function getCorrectionType(): ?string
    {
        return $this->rectificativa ? 'S' : null;
    }

    public function getExternalReference(): ?string
    {
        return (string) $this->id;
    }

    public function getCorrectedBaseAmount(): ?float
    {
        return $this->rectificativa ? (float) optional($this->facturaOriginal)->baseimponible : null;
    }

    public function getCorrectedTaxAmount(): ?float
    {
        return $this->rectificativa ? (float) optional($this->facturaOriginal)->impuesto : null;
    }

    public function getCorrectedSurchargeAmount(): ?float
    {
        return null;
    }

    public function facturaOriginal()
    {
        return $this->belongsTo(Factura::class, 'factura_original_id');
    }
}
