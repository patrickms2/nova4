<?php

declare(strict_types=1);

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Servicio extends Model
{
    use HasFactory;

    protected $table = 'taxis_servicios';

    protected $fillable = [
        'nombre',
        'usuario_id',
        'tipotaxi_id',
        'municipio_id',
        'estado_id',
        'fecha_servicio',
        'fecha_terminado',
        'habitacion',
        'personas',
        'observaciones',
        'tarjeta_credito',
        'bookingId',
        'respuesta',
        'extras',
        'operador_id',
        'taxi_id',
        'taxista_id',
        'conductor_id',
    ];

    protected $casts = [
        'fecha_servicio' => 'datetime',
        'fecha_terminado' => 'datetime',
        'tarjeta_credito' => 'boolean',
        'personas' => 'integer',
        'habitacion' => 'integer',
    ];

    protected $attributes = [
        'personas' => 2,
    ];

    /**
     * Índices para mejorar rendimiento de consultas
     */
    protected $indexes = [
        'usuario_id',
        'taxi_id',
        'taxista_id',
        'conductor_id',
        'bookingId',
        'estado_id',
        'tipotaxi_id',
        'municipio_id',
        'fecha_servicio',
    ];


    /**
     * Set search query for the model
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $text
     */
    public static function search($query, $text){
        //search table record
        $search_condition = '(
				id LIKE ?  OR
				nombre LIKE ?  OR
				observaciones LIKE ?  OR
				habitacion LIKE ?  OR
				respuesta LIKE ?  OR
				nombre_cliente LIKE ?  OR
				tfno_cliente LIKE ?  OR
				bookingId LIKE ?  OR
				extras LIKE ?
		)';
        $search_params = [
            "%$text%","%$text%","%$text%","%$text%","%$text%","%$text%","%$text%","%$text%","%$text%"
        ];
        //setting search conditions
        $query->whereRaw($search_condition, $search_params);

    }


    /**
     * return list page fields of the model.
     *
     * @return array
     */
    public static function listFields(){
        return [
            "id",
            "nombre",
            "estado_id",
            "personas",
            "fecha_servicio",
            "fecha_terminado",
            "fecha_alta",
            "observaciones",
            "usuario_id",
            "tipotaxi_id",
            "municipio_id",
            "habitacion",
            "tarjeta_credito",
            "respuesta",
            "codoperador",
            "nombre_cliente",
            "tfno_cliente",
            "bookingId AS bookingid",
            "extras"
        ];
    }



    /**
     * return exportList page fields of the model.
     *
     * @return array
     */
    public static function exportListFields(){
        return [
            "id",
            "nombre",
            "estado_id",
            "personas",
            "fecha_servicio",
            "fecha_terminado",
            "fecha_alta",
            "observaciones",
            "usuario_id",
            "tipotaxi_id",
            "municipio_id",
            "habitacion",
            "tarjeta_credito",
            "respuesta",
            "codoperador",
            "nombre_cliente",
            "tfno_cliente",
            "bookingId AS bookingid",
            "extras"
        ];
    }



    /**
     * return view page fields of the model.
     *
     * @return array
     */
    public static function viewFields(){
        return [
            "id",
            "nombre",
            "estado_id",
            "personas",
            "fecha_servicio",
            "fecha_terminado",
            "fecha_alta",
            "observaciones",
            "usuario_id",
            "tipotaxi_id",
            "municipio_id",
            "habitacion",
            "tarjeta_credito",
            "respuesta",
            "codoperador",
            "nombre_cliente",
            "tfno_cliente",
            "bookingId AS bookingid",
            "extras"
        ];
    }



    /**
     * return exportView page fields of the model.
     *
     * @return array
     */
    public static function exportViewFields(){
        return [
            "id",
            "nombre",
            "estado_id",
            "personas",
            "fecha_servicio",
            "fecha_terminado",
            "fecha_alta",
            "observaciones",
            "usuario_id",
            "tipotaxi_id",
            "municipio_id",
            "habitacion",
            "tarjeta_credito",
            "respuesta",
            "codoperador",
            "nombre_cliente",
            "tfno_cliente",
            "bookingId AS bookingid",
            "extras"
        ];
    }



    /**
     * return edit page fields of the model.
     *
     * @return array
     */
    public static function editFields(){
        return [
            "id",
            "nombre",
            "estado_id",
            "personas",
            "fecha_servicio",
            "fecha_terminado",
            "fecha_alta",
            "observaciones",
            "usuario_id",
            "tipotaxi_id",
            "municipio_id",
            "habitacion",
            "tarjeta_credito",
            "respuesta",
            "codoperador",
            "nombre_cliente",
            "tfno_cliente",
            "bookingId AS bookingid",
            "extras"
        ];
    }
    /**
     * Obtener alias para compatibilidad con el sistema antiguo
     */
    public function getCodservicioAttribute()
    {
        return $this->id;
    }

    public function getCodusuarioAttribute()
    {
        return $this->usuario_id;
    }

    public function getCodestadoAttribute()
    {
        return $this->estado_id;
    }

    public function getCodtipotaxiAttribute()
    {
        return $this->tipotaxi_id;
    }

    public function getCodmunicipioAttribute()
    {
        return $this->municipio_id;
    }

    public function getCodoperadorAttribute()
    {
        return $this->operador_id; // Valor por defecto según el ejemplo
    }

    public function getBookingIdAttribute()
    {
        $booking = $this->booking;

        return $booking ? $booking->booking_id : null;
    }

    /**
     * Obtener el primer valor de extras
     */
    public function getFirstExtraAttribute()
    {
        if (empty($this->extras)) {
            return null;
        }
        $extras = explode(',', $this->extras);

        return isset($extras[0]) ? $extras[0] : null;
    }

    /**
     * Obtener todos los valores de extras como array
     */
    public function getExtrasArrayAttribute()
    {
        if (empty($this->extras)) {
            return [];
        }

        return explode(',', $this->extras);
    }

    /**
     * Obtener el nombre del tipo de taxi
     */
    public function getNombreTipoAttribute()
    {
        return $this->tipotaxi ? $this->tipotaxi->nombre : 'NORMAL';
    }

    /**
     * Obtener el nombre del estado
     */
    public function getNombreEstadoAttribute()
    {
        return $this->estado ? $this->estado->nombre : 'PENDIENTE';
    }

    /**
     * Obtener el nombre del municipio
     */
    public function getNombreMunicipioAttribute()
    {
        return $this->municipio ? $this->municipio->nombre : '';
    }

    /**
     * Obtener el nombre del usuario
     */
    public function getNombreUsuarioAttribute()
    {
        return $this->usuario ? $this->usuario->nombre : '';
    }

    /**
     * Obtener objeto Tipo para el formato de respuesta
     */
    public function getTipoObjectAttribute()
    {
        return [
            'codtipotaxi' => $this->tipotaxi_id,
            'nombreTipo' => $this->nombre_tipo,
        ];
    }

    /**
     * Obtener objeto Estado para el formato de respuesta
     */
    public function getEstadoObjectAttribute()
    {
        return [
            'codestado' => $this->estado_id,
            'nombreEstado' => $this->nombre_estado,
        ];
    }

    /**
     * Obtener objeto Municipio para el formato de respuesta
     */
    public function getMunicipioObjectAttribute()
    {
        return [
            'codmunicipio' => $this->municipio_id,
            'nombreMunicipio' => $this->nombre_municipio,
        ];
    }

    /**
     * Obtener objeto Usuario para el formato de respuesta
     */
    public function getUsuarioObjectAttribute()
    {
        return [
            'codusuario' => $this->usuario_id,
            'nombreUsuario' => $this->nombre_usuario,
        ];
    }

    /**
     * Relación con el usuario (hotel)
     */
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id')
            ->select(['id', 'nombre', 'email', 'tel_fijo', 'tipo_id'])->where('tipo_id', 2);
    }

    public function operador()
    {
        return $this->belongsTo(Usuario::class, 'operador_id')
            ->select(['id', 'nombre', 'email'])->where('tipo_id', 1);
    }

    public function conductor()
    {
        return $this->belongsTo(Usuario::class, 'conductor_id')
            ->select(['id', 'nombre', 'email'])->where('tipo_id', 8);
    }

    public function taxista()
    {
        return $this->belongsTo(Taxista::class, 'taxista_id')
            ->select(['id', 'nombre', 'email']);
    }

    public function taxi()
    {
        return $this->belongsTo(Taxi::class, 'taxi_id')
            ->select(['id', 'matricula']);
    }

    /**
     * Relación con el tipo de taxi
     */
    public function tipotaxi()
    {
        return $this->belongsTo(TipoTaxis::class, 'tipotaxi_id')
            ->select(['id', 'nombre', 'capacidad']);
    }

    /**
     * Relación con el municipio
     */
    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id')
            ->select(['id', 'nombre']);
    }

    /**
     * Relación con el estado del servicio
     */
    public function estado()
    {
        return $this->belongsTo(EstadosServicio::class, 'estado_id')
            ->select(['id', 'nombre']);
    }

    /**
     * Relación con la información de booking
     */
    public function booking()
    {
        return $this->hasOne(Booking::class, 'servicio_id');
    }

    /**
     * Scope para filtrar servicios pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado_id', 1);
    }

    /**
     * Scope para filtrar servicios en proceso
     */
    public function scopeEnProceso($query)
    {
        return $query->where('estado_id', 2);
    }

    /**
     * Scope para filtrar servicios completados
     */
    public function scopeCompletados($query)
    {
        return $query->where('estado_id', 3);
    }

    /**
     * Scope para filtrar servicios cancelados
     */
    public function scopeCancelados($query)
    {
        return $query->where('estado_id', 4);
    }

    /**
     * Scope para filtrar servicios por fecha
     */
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha_servicio', $fecha);
    }

    /**
     * Scope para filtrar servicios del día actual
     */
    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_servicio', now()->toDateString());
    }

    /**
     * Scope para filtrar servicios entre fechas
     */
    public function scopeEntreFechas($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_servicio', [$desde, $hasta]);
    }
}
