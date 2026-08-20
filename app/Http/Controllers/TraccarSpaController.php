<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TraccarSpaController extends Controller
{
    public function __invoke(string $path = ''): SymfonyResponse
    {
        $buildDirectory = realpath(public_path('traccar/build'));

        abort_if($buildDirectory === false, Response::HTTP_NOT_FOUND);

        $normalizedPath = trim($path, '/');

        if ($normalizedPath !== '') {
            $filePath = realpath($buildDirectory . DIRECTORY_SEPARATOR . $normalizedPath);

            if ($filePath !== false && str_starts_with($filePath, $buildDirectory) && is_file($filePath)) {
                return response()->file($filePath);
            }
        }

        $indexPath = $buildDirectory . DIRECTORY_SEPARATOR . 'index.html';

        abort_unless(is_file($indexPath), Response::HTTP_NOT_FOUND);

        return response()->file($indexPath);
    }
}
