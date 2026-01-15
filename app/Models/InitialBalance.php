<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InitialBalance extends Model
{
    protected $fillable = [
        'company_id',
        'waste_name',
        'fkko_code',
        'hazard_class',
        'amount',
        'year',
        'period'
    ];
}
