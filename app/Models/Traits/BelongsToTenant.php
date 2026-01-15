<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\UserCompany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->company_id && $tenantId = app(\App\Services\TenantService::class)->id()) {
                $model->company_id = $tenantId;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(UserCompany::class, 'company_id');
    }
}
