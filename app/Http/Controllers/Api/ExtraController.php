<?php

namespace App\Http\Controllers\Api;

use App\Models\Extra;
use Illuminate\Http\Request;
use App\Http\Requests\ExtraRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExtraResource;

class ExtraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit');
        $extras = Extra::paginate($limit);

        return ExtraResource::collection($extras);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExtraRequest $request): Extra
    {
        return Extra::create($request->validated());
    }

    /**
     * Display the specified resource.
     */
    public function show(Extra $extra): Extra
    {
        return $extra;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExtraRequest $request, Extra $extra): Extra
    {
        $extra->update($request->validated());

        return $extra;
    }

    public function destroy(Extra $extra): Response
    {
        $extra->delete();

        return response()->noContent();
    }
}
