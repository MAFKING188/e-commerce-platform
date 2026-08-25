<?php

namespace Modules\PartnerHub\Models;

use Illuminate\Database\Eloquent\Model;

class VendorBankDetail extends Model
{
    protected $table = 'vendor_bank_details';

    protected $fillable = [
        'partner_id',
        'bank_details_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }
}