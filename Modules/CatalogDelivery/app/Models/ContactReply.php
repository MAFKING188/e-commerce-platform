<?php

namespace Modules\CatalogDelivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactReply extends Model
{
    protected $fillable = [
        'contact_message_id',
        'body',
        'admin_name',
        'admin_email',
    ];

    public function contactMessage(): BelongsTo
    {
        return $this->belongsTo(ContactMessage::class);
    }
}
