<?php

namespace App\Http\Controllers\Api;

use App\Models\Taxi\Municipio;
use Illuminate\Http\Request;
use App\Http\Requests\MunicipioRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\MunicipioResource;

class MunicipioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit');
        $municipios = Municipio::paginate($limit);

        return MunicipioResource::collection($municipios);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MunicipioRequest $request): Municipio
    {
        return Municipio::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Municipio $municipio): Municipio
    {
        return $municipio;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MunicipioRequest $request, Municipio $municipio): Municipio
    {
        $municipio->update($request->validated());

        return $municipio;
    }

    public function destroy(Municipio $municipio): Response
    {
        $municipio->delete();

        return response()->noContent();
    }
}
