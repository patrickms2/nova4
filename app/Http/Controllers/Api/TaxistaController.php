<?php

namespace App\Http\Controllers\Api;

use App\Models\Taxi\Usuario as Taxista;
use Illuminate\Http\Request;
use App\Http\Requests\TaxistaRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaxistaResource;

class TaxistaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit');
        $id = $request->input('id');

if($id){
        $taxistas = Taxista::find($id)->orderBy("id", "DESC")->with('municipio')->paginate($limit);
}else{
        $taxistas = Taxista::query()->orderBy("id", "DESC")->with('municipio')->paginate($limit);
}

        return TaxistaResource::collection($taxistas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaxistaRequest $request): Taxista
    {
        return Taxista::create($request->validated());
    }

    // Actualizar un coche específico por su ID
    public function store2(Request $request, $id)
    {
        $taxista = new Taxista;
        if ($taxista) {
            $taxista->nombre = $request->input('nombre');
            $taxista->apellidos = $request->input('apellidos');
            $taxista->dni = $request->input('dni');
            $taxista->licencia = $request->input('licencia');
            $taxista->municipio_id = $request->input('municipio_id');
            $taxista->password = $request->input('password');
            $taxista->estado = $request->input('estado');
            $taxista->email = $request->input('email');
            $taxista->telefono = $request->input('telefono');
            $taxista->observaciones = $request->input('observaciones');
            $taxista->documentos = $request->input('documentos');
            $taxista->sort = $request->input('sort');
            $taxista->save();
            return response()->json($taxista, 200);
        } else {
            return response()->json(['message' => 'Taxista no encontrado'], 404);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Taxista $taxista): Taxista
    {
        return $taxista;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaxistaRequest $request, Taxista $taxista): Taxista
    {

        var_dump($request);

        $taxista->update($request->validated());

        return $taxista;
    }

    // Actualizar un coche específico por su ID
    public function update2(Request $request, $id)
    {
        $taxista = Taxista::find($id);
        if ($taxista) {
            $taxista->nombre = $request->input('nombre');
            $taxista->apellidos = $request->input('apellidos');
            $taxista->dni = $request->input('dni');
            $taxista->licencia = $request->input('licencia');
            $taxista->municipio_id = $request->input('municipio_id');
            $taxista->password = $request->input('password');
            $taxista->estado = $request->input('estado');
            $taxista->email = $request->input('email');
            $taxista->telefono = $request->input('telefono');
            $taxista->observaciones = $request->input('observaciones');
            $taxista->documentos = $request->input('documentos');
            $taxista->sort = $request->input('sort');
            $taxista->save();
            return response()->json($taxista, 200);
        } else {
            return response()->json(['message' => 'Taxista no encontrado'], 404);
        }
    }

  public function listado(Request $request)
    {

        $limit = $request->input('limit');
        $id = $request->id;

      /*print_r($request->id);*/



$where = ["usuarios.id" => $id];

$taxistas = Taxista::select(
  "usuarios.id",
  "usuarios.nombre",
  "usuarios.cif",
  "usuarios.licencia",
  "usuarios.municipio_id",
  "usuarios.email",
  "usuarios.tel_fijo",
  "usuarios.estado_id",
  "usuarios.tipo_id",

  "municipios.nombre as municipio_nombre",
    "tipo.nombre"

)
  ->join("tipos_usuarios as tipo", "tipo.id", "=", "usuarios.tipo_id")

  ->join("municipios", "municipios.id", "=", "usuarios.municipio_id")
  ->orWhere($where)
  ->with("municipio")
  ->with("servicios")
  ->with("documentos")
  ->with("taxis")
  ->orderBy("id", "desc")
  ->get()->toArray();

    if ($taxistas) {

            return response()->json($taxistas[0], 200);
        } else {
            return response()->json(['message' => 'Taxista no encontrado'], 404);
    }
}



    public function destroy(Taxista $taxista): Response
    {
        $taxista->delete();

        return response()->noContent();
    }
}
