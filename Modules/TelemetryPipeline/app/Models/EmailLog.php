<?php

namespace Modules\TelemetryPipeline\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = ['recipient', 'subject', 'status'];
}