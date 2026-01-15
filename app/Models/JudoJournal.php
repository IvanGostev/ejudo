<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JudoJournal extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'period',
        'company_id',
        'role',
        'table1_data',
        'table2_data',
        'table3_data',
        'table4_data',
        'pdf_path',
        'is_paid',
        'downloaded_at',
    ];

    protected $casts = [
        'period' => 'date',
        'table1_data' => 'array',
        'table2_data' => 'array',
        'table3_data' => 'array',
        'table4_data' => 'array',
        'is_paid' => 'boolean',
        'downloaded_at' => 'datetime',
    ];
}
