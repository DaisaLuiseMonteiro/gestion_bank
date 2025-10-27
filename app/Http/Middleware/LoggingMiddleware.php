<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Facades\AuditLog;
use App\Models\Admin;

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

            $user = $request->user();
            $adminId = null;
            $userId = null;
            if ($user) {
                $userId = $user->id;
                $adminId = Admin::where('user_id', $user->id)->value('id');
            }

            $payload = null;
            try {
                $payload = $request->all();
            } catch (\Throwable $e) {
                $payload = null;
            }

            AuditLog::write([
                'admin_id' => $adminId,
                'user_id' => $userId,
                'operation' => $operation,
                'resource' => 'comptes',
                'method' => $method,
                'path' => $path,
                'ip' => $request->ip(),
                'message' => $operation.' compte',
                'payload' => $payload,
                'status_code' => $response->getStatusCode(),
            ]);
        }

        return $response;
    }
}
