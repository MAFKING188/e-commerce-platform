<?php

namespace Modules\TelemetryPipeline\Services;

use Modules\TelemetryPipeline\Models\AuditLog;

class TelemetryService
{
    public function log(string $action, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'metadata' => $metadata,
            'ip' => request()->ip(),
        ]);
    }
}