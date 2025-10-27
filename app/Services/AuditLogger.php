<?php

namespace App\Services;

use App\Models\OperationLog;
use Illuminate\Contracts\Auth\Authenticatable;

class AuditLogger
{
    public function write(array $data): OperationLog
    {
        return OperationLog::create([
            'admin_id' => $data['admin_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'operation' => $data['operation'] ?? 'other',
            'resource' => $data['resource'] ?? 'comptes',
            'method' => $data['method'] ?? null,
            'path' => $data['path'] ?? null,
            'ip' => $data['ip'] ?? null,
            'message' => $data['message'] ?? null,
            'payload' => $data['payload'] ?? null,
            'status_code' => $data['status_code'] ?? null,
        ]);
    }
}
