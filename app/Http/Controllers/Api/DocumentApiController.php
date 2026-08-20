<?php

namespace App\Http\Controllers\Api;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Jobs\ProcessDocumentJob;
use App\Models\Taxi\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class DocumentApiController extends Controller
{
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $default_disk = config('filesystems.default');
        $document = Document::create([
            'id' => Str::uuid(),
            'status' => Status::PENDING,
            'options' => $validated['operations'],
            'file' => $validated['document_file']->store('documents', $default_disk),
            'type' => strtolower($validated['document_file']->getClientOriginalExtension()),
            'ai_model' => $validated['ai_model']
        ]);

        ProcessDocumentJob::dispatch($document);

        return response()->json([
            'id' => $document->id
        ], 202);
    }

    public function status(Document $document): JsonResponse
    {
        $document->message = $document->status->message();
        return response()->json($document);
    }
}
