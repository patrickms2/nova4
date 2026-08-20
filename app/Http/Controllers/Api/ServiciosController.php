<?php

namespace App\Http\Controllers\Api;

use App\Models\Taxi\Servicio;
use App\Models\Taxi\Usuario;
use Illuminate\Http\Request;
use App\Http\Requests\ServicioRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServicioResource;

class ServiciosController extends Controller
{

    function index(Request $request, $fieldname = null , $fieldvalue = null){
        $query = Servicio::query();
        if($request->search){
            $search = trim($request->search);
            Servicio::search($query, $search);
        }
        $query->join("usuarios", "usuarios.id", "=", "taxis_servicios.usuario_id");
        $orderby = $request->orderby ?? "usuarios.id";
        $ordertype = $request->ordertype ?? "desc";
        $query->orderBy($orderby, $ordertype);
        if($fieldname){
            $query->where($fieldname , $fieldvalue); //filter by a single field name
        }
        // if request format is for export example:- product/index?export=pdf
        if($this->getExportFormat()){
            return $this->ExportList($query); // export current query
        }
        $records = $this->paginate($query, Servicio::listFields());
        return $this->respond($records);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(ServicioRequest $request): Servicio
    {
        return Servicio::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Servicio $servicio): Servicio
    {
        return $servicio;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ServicioRequest $request, Servicio $servicio): Servicio
    {
        $servicio->update($request->validated());

        return $servicio;
    }

    public function destroy(Servicio $servicio): Response
    {
        $servicio->delete();

        return response()->noContent();
    }
}
