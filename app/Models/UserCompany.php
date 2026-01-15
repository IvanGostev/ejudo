<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserCompany extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'inn',
        'kpp',
        'ogrn',
        'legal_address',
        'actual_address',
        'contact_person',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'company_id');
    }

    public function acts(): HasMany
    {
        return $this->hasMany(Act::class, 'company_id');
    }

    public function wasteBalances(): HasMany
    {
        return $this->hasMany(WasteBalance::class, 'company_id');
    }

    public function judoJournals(): HasMany
    {
        return $this->hasMany(JudoJournal::class, 'company_id');
    }

    public function getFullFormalNameAttribute(): string
    {
        $prefix = ($this->type === 'ip') ? 'Индивидуальный предприниматель' : 'Общество с ограниченной ответственностью';
        return "{$prefix} \"{$this->name}\"";
    }
}
