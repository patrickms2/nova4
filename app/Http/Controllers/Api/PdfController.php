<?php

namespace App\Http\Controllers\Api;

use App\Models\Taxi\Documento as Pdf;
use Illuminate\Http\Request;
use App\Http\Requests\PdfRequest;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Resources\PdfResource;

class PdfController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
            $limit = $request->input('limit');
        $id = $request->id;
        $pdfs = Pdf::orderBy("id", "DESC")->paginate($limit);
        return PdfResource::collection($pdfs);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PdfRequest $request): Pdf
    {
        return Pdf::create($request->validated());
    }


    // Actualizar un coche específico por su ID
    public function store2(Request $request, $id)
    {
        $pdf = new Pdf;
        if ($pdf) {
            $pdf->nombre = $request->input('nombre');

            $pdf->save();
            return response()->json($pdf, 200);
        } else {
            return response()->json(['message' => 'Pdf no encontrado'], 404);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Pdf $pdf): Pdf
    {
        return $pdf;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PdfRequest $request, Pdf $pdf): Pdf
    {
        $pdf->update($request->validated());

        return $pdf;
    }

    public function destroy(Pdf $pdf): Response
    {
        $pdf->delete();

        return response()->noContent();
    }
}
