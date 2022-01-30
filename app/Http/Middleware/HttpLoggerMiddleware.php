<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response As HttpResponse;

class HttpLoggerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->header('X-Request-Id', Str::random(10));

        return $response;
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param \Illuminate\Http\Request  $request
     * @param \Illuminate\Http\Response $response
     *
     * @return void
     */
    public function terminate(Request $request, $response)
    {
        $duration = microtime(true) - LARAVEL_START;

        $identifier = $response->headers->get('X-Request-Id');

        $this->logRequest($identifier, $request);

        $this->logResponse($identifier, $response, $duration);
    }

    /**
     * Log request to file.
     *
     * @param string $identifier
     * @param \Illuminate\Http\Request $request
     *
     * @return int \Illuminate\Http\Request Log ID
     */
    public function logRequest(string $identifier, Request $request)
    {
        $protocolVersion = $request->getProtocolVersion();

        $method = strtoupper($request->getMethod());

        $uri = $request->getPathInfo();

        Log::info("HTTP_LOG_{$identifier} [REQUEST] {$method} {$uri} {$protocolVersion}");
        Log::debug("HTTP_LOG_{$identifier} [REQUEST] [Headers]\n\r" . $request->headers->__toString());
        Log::debug("HTTP_LOG_{$identifier} [REQUEST] [Body]\n\r" . $request->getContent());
    }

    /**
     * Log response to file.
     * 
     * @see https://github.com/symfony/http-foundation/blob/5.4/Response.php
     *
     * @param string                    $identifier
     * @param \Illuminate\Http\Response $response
     * @param int                       $duration Seconds.
     *
     * @return void
     */
    public function logResponse(string $identifier, $response, int $duration = 0)
    {
        $statusCode = $response->getStatusCode();

        $version = $response->getProtocolVersion();

        $statusText = HttpResponse::$statusTexts[$statusCode];

        $duration = number_format($duration, 2);

        Log::info("HTTP_LOG_{$identifier} [RESPONSE] HTTP/{$version} {$statusCode} {$statusText} {$duration}s");
        Log::debug("HTTP_LOG_{$identifier} [RESPONSE] [Headers]\n\r" . $response->headers->__toString());
        Log::debug("HTTP_LOG_{$identifier} [RESPONSE] [Body]\n\r" . $response->getContent());
    }
}
