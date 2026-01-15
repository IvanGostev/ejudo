<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FkkoCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'hazard_class',
        'unit',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
