<?php

namespace App\Http\Controllers\Api;

use App\Models\Categoria;
use Illuminate\Http\Request;
use App\Http\Requests\CategoriaRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit');
        $categorias = Categoria::paginate($limit);

        return CategoriaResource::collection($categorias);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoriaRequest $request): Categoria
    {
        return Categoria::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Categoria $categoria): Categoria
    {
        return $categoria;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CategoriaRequest $request, Categoria $categoria): Categoria
    {
        $categoria->update($request->validated());

        return $categoria;
    }

    public function destroy(Categoria $categoria): Response
    {
        $categoria->delete();

        return response()->noContent();
    }
}
