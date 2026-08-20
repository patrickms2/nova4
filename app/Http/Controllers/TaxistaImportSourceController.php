<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TaxistaImportSourceController extends Controller
{
    public function __invoke(string $path): Response|BinaryFileResponse
    {
        try {
            $decryptedPath = Crypt::decryptString($path);
        } catch (DecryptException) {
            abort(404);
        }

        $realRequestedPath = realpath($decryptedPath);
        $allowedRoots = array_filter([
            realpath(storage_path('app/private/imports/taxista-documents/multipage')),
            realpath(storage_path('app/private/livewire-tmp')),
        ]);

        if ($realRequestedPath === false || $allowedRoots === []) {
            abort(404);
        }

        $isAllowed = collect($allowedRoots)->contains(function (string $root) use ($realRequestedPath): bool {
            return Str::startsWith($realRequestedPath, $root . DIRECTORY_SEPARATOR) || $realRequestedPath === $root;
        });

        if (!$isAllowed) {
            abort(403);
        }

        if (!is_file($realRequestedPath)) {
            abort(404);
        }

        return response()->file($realRequestedPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($realRequestedPath) . '"',
        ]);
    }
}
