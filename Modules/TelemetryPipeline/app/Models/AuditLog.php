<?php

namespace Modules\TelemetryPipeline\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['actor_id', 'action', 'metadata', 'ip'];

    protected $casts = [
        'metadata' => 'array',
    ];
}