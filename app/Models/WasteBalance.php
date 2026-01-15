<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteBalance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'fkko_code',
        'period',
        'quantity',
        'unit',
    ];

    protected $casts = [
        'period' => 'date',
        'quantity' => 'decimal:3',
    ];

    public function fkko(): BelongsTo
    {
        return $this->belongsTo(FkkoCode::class, 'fkko_code', 'code');
    }
}
