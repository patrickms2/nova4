<?php

namespace App\Http\Controllers;

use App\Services\TraccarService;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class TraccarProxyController extends Controller
{
    public function __invoke(Request $request, TraccarService $traccarService, string $path = ''): SymfonyResponse
    {
        abort_if($path === 'socket', Response::HTTP_NOT_FOUND);

        if (! $traccarService->ensureAuthenticated()) {
            abort(Response::HTTP_BAD_GATEWAY, 'Unable to authenticate against Traccar.');
        }

        $traccarService->loadCookies();

        $targetUrl = rtrim($traccarService->baseUrl, '/') . '/' . ltrim($path, '/');
        $host = (string) parse_url($traccarService->baseUrl, PHP_URL_HOST);
        $contentType = $request->header('Content-Type', 'application/json');

        $client = \Illuminate\Support\Facades\Http::withCookies($traccarService->cookies, $host)
            ->withHeaders($this->forwardHeaders($request));

        if ($request->getContent() !== '' && ! in_array($request->method(), ['GET', 'HEAD'], true)) {
            $client = $client->withBody($request->getContent(), $contentType);
        }

        $upstreamResponse = $client->send($request->method(), $targetUrl, [
            'query' => $request->query(),
        ]);

        $traccarService->storeCookies($upstreamResponse);
        session()->put('traccar_cookies', $traccarService->cookies);

        return response(
            $upstreamResponse->body(),
            $upstreamResponse->status(),
            $this->responseHeaders($upstreamResponse),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function forwardHeaders(Request $request): array
    {
        $allowedHeaders = [
            'Accept',
            'Content-Type',
            'If-Modified-Since',
            'If-None-Match',
            'Range',
        ];

        $headers = [];

        foreach ($allowedHeaders as $header) {
            $value = $request->header($header);

            if (filled($value)) {
                $headers[$header] = (string) $value;
            }
        }

        return $headers;
    }

    /**
     * @return array<string, string>
     */
    protected function responseHeaders(HttpResponse $response): array
    {
        $allowedHeaders = [
            'Content-Type',
            'Content-Disposition',
            'Cache-Control',
            'ETag',
            'Last-Modified',
        ];

        $headers = [];

        foreach ($allowedHeaders as $header) {
            $value = $response->header($header);

            if (filled($value)) {
                $headers[$header] = is_array($value) ? implode(', ', $value) : (string) $value;
            }
        }

        return $headers;
    }
}
