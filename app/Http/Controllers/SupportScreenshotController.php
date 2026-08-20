<?php

namespace App\Http\Controllers;

use App\Models\TaxistaTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportScreenshotController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'ticket_id' => ['required', 'integer', 'exists:taxista_tickets,id'],
            'screenshot' => ['required', 'string'],
        ]);

        $ticket = TaxistaTicket::findOrFail((int) $request->input('ticket_id'));

        $imageData = (string) $request->input('screenshot');
        $imageData = (string) preg_replace('#^data:image/\w+;base64,#i', '', $imageData);
        $imageBytes = base64_decode($imageData, true);

        if ($imageBytes === false) {
            return response()->json(['error' => 'Imagen inválida'], 422);
        }

        $filename = 'tickets-attachments/screenshot-' . Str::ulid() . '.png';
        Storage::disk('public')->put($filename, $imageBytes);

        $attachments = $ticket->attachments ?? [];
        $attachments[] = $filename;

        $fileNames = $ticket->attachment_file_names ?? [];
        $fileNames[$filename] = 'Captura remota — ' . now()->format('d/m/Y H:i');

        $ticket->update([
            'attachments' => $attachments,
            'attachment_file_names' => $fileNames,
            'is_screen_shot' => false,
        ]);

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'message' => 'Captura guardada en el ticket #' . $ticket->id,
        ]);
    }
}
