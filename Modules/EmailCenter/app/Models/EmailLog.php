<?php

namespace Modules\EmailCenter\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\IdentityAccess\Models\User;
use Modules\EmailCenter\Models\EmailTemplate;

class EmailLog extends Model
{
    protected $table = 'email_center_logs';

    protected $fillable = [
        'batch_id',
        'sender_user_id',
        'sender_role',
        'recipient_email',
        'template_id',
        'subject',
        'body_markdown',
        'status',
        'error',
    ];

    protected $casts = [
        'batch_id' => 'string',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }
}