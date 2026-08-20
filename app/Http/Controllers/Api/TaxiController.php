<?php

namespace App\Http\Controllers\Api;

use App\Models\Taxi\Taxi;
use Illuminate\Http\Request;
use App\Http\Requests\TaxiRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaxiResource;

class TaxiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit');
        $taxis = Taxi::orderBy("id", "DESC")->paginate($limit);

        return TaxiResource::collection($taxis);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TaxiRequest $request): Taxi
    {
        return Taxi::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Taxi $taxi): Taxi
    {
        return $taxi;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TaxiRequest $request, Taxi $taxi): Taxi
    {
        $taxi->update($request->validated());

        return $taxi;
    }

    public function destroy(Taxi $taxi): Response
    {
        $taxi->delete();

        return response()->noContent();
    }
}
