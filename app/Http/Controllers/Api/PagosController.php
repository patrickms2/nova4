<?php

namespace App\Http\Controllers\Api;

use App\Models\Taxi\Pago;
use Illuminate\Http\Request;
use App\Http\Requests\PagoRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\PagoResource;

class PagosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit');
        $pagos = Pago::orderBy("id", "DESC")->paginate($limit);

        return PagoResource::collection($pagos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PagoRequest $request): Pago
    {
        ds($request);
        return Pago::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Pago $pago): Pago
    {
        return $pago;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PagoRequest $request, Pago $pago): Pago
    {
        $pago->update($request->validated());

        return $pago;
    }

    public function destroy(Pago $pago): Response
    {
        $pago->delete();

        return response()->noContent();
    }
}
