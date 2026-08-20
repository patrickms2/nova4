<?php

namespace App\Http\Controllers\Api;

use App\Models\Departamento;
use Illuminate\Http\Request;
use App\Http\Requests\DepartamentoRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\DepartamentoResource;

class DepartamentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit');
        $departamentos = Departamento::orderBy("id", "DESC")->paginate($limit);

        return DepartamentoResource::collection($departamentos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DepartamentoRequest $request): Departamento
    {
        return Departamento::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Departamento $departamento): Departamento
    {
        return $departamento;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DepartamentoRequest $request, Departamento $departamento): Departamento
    {
        $departamento->update($request->validated());

        return $departamento;
    }

    public function destroy(Departamento $departamento): Response
    {
        $departamento->delete();

        return response()->noContent();
    }
}
