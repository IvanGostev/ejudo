<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Polygon extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'address',
        'description',
        'area',
        'waste_types',
        'capacity',
        'current_load',
        'status',
        'coordinates',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'capacity' => 'decimal:2',
        'current_load' => 'decimal:2',
        'waste_types' => 'array',
        'coordinates' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(UserCompany::class, 'company_id');
    }

    public function wasteBalances()
    {
        return $this->hasMany(WasteBalance::class, 'polygon_id');
    }

    public function judoJournals()
    {
        return $this->hasMany(JudoJournal::class, 'polygon_id');
    }
}
