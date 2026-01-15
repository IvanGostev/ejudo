<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Act extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'filename',
        'original_name',
        'file_size',
        'act_data',
        'status',
        'processing_result',
    ];

    protected $casts = [
        'act_data' => 'array',
        'processing_result' => 'array',
    ];
}
