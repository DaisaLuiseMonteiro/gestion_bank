<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoggingMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $method = strtoupper($request->getMethod());
        $path = $request->path();
        $isTarget = preg_match('#^api/monteiro\.daisa/v1/comptes(/.*)?$#', $path) === 1;

        if ($isTarget && in_array($method, ['POST','PUT','PATCH','DELETE'])) {
            $operation = match ($method) {
                'POST' => 'create',
                'PUT', 'PATCH' => 'update',
                'DELETE' => 'delete',
                default => 'other',
            };

            Log::info('Compte operation', [
                'timestamp' => now()->toISOString(),
                'host' => $request->getHost(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'operation' => $operation,
                'resource' => 'comptes',
                'method' => $method,
                'path' => $path,
                'status' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
