<?php

namespace Modules\CatalogDelivery\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactMessage extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'message',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ContactReply::class);
    }
}
