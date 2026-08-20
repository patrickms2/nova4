<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    /** @use HasFactory<\Database\Factories\GastoFactory> */
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'proveedor_id',
        'transaction_id',
        'codigo',
        'descripcion',
        'description',
        'notas',
        'categoria',
        'type',
        'category_id',
        'fecha',
        'date',
        'base_imponible',
        'impuesto',
        'total',
        'amount',
        'estado',
        'metodo_pago',
        'documento',
        'deducible',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'base_imponible' => 'decimal:2',
            'impuesto' => 'decimal:2',
            'total' => 'decimal:2',
            'deducible' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'proveedor_id');
    }
 public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)
            ->withDefault(['name' => '—']);
    }

    public function attachments()
    {
        return $this->hasMany(TransactionAttachment::class);
    }

    public function latestAttachment()
    {
        return $this->hasOne(TransactionAttachment::class)->latestOfMany();
    }

    protected static function booted(): void
    {
        static::creating(function (Gasto $gasto): void {
            if (! $gasto->codigo) {
                $fecha = $gasto->fecha ?? now();
                $contador = static::query()->whereDate('fecha', $fecha->toDateString())->count();
                $gasto->codigo = 'G-'.$fecha->format('Ymd').'-'.str_pad((string) ($contador + 1), 4, '0', STR_PAD_LEFT);
            }

            if (! $gasto->type) {
                $gasto->type = 'expense';
            }

            if ($gasto->total == 0 && $gasto->base_imponible > 0) {
                $gasto->total = round((float) $gasto->base_imponible + (float) $gasto->impuesto, 2);
            }

            if ($gasto->base_imponible == 0 && $gasto->total > 0) {
                $gasto->base_imponible = round((float) $gasto->total - (float) $gasto->impuesto, 2);
            }
        });
    }

    /**
     * Alias attribute: description <-> descripcion.
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->descripcion,
            set: fn ($value) => ['descripcion' => $value],
        );
    }

    /**
     * Alias attribute: amount <-> total.
     */
    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->total,
            set: fn ($value) => ['total' => $value],
        );
    }

    /**
     * Alias attribute: date <-> fecha.
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fecha,
            set: fn ($value) => ['fecha' => $value],
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function estados(): array
    {
        return [
            'pendiente' => 'Pendiente',
            'pagado' => 'Pagado',
            'cancelado' => 'Cancelado',
        ];
    }

    public static function categorias(): array
    {
        return [
            'suministros' => 'Suministros',
            'alquiler' => 'Alquiler',
            'nomina' => 'Nómina',
            'seguros' => 'Seguros',
            'servicios' => 'Servicios profesionales',
            'marketing' => 'Marketing y publicidad',
            'impuestos' => 'Impuestos y tasas',
            'mantenimiento' => 'Mantenimiento',
            'otros' => 'Otros',
        ];
    }
}
