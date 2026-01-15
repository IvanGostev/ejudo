<?php

namespace App\Services;

use App\Models\UserCompany;
use Illuminate\Support\Facades\Session;

class TenantService
{
    protected ?UserCompany $company = null;

    public function setCompany(UserCompany $company): void
    {
        $this->company = $company;
        Session::put('tenant_id', $company->id);
    }

    public function getCompany(): ?UserCompany
    {
        if ($this->company) {
            return $this->company;
        }

        if (Session::has('tenant_id')) {
            $this->company = UserCompany::find(Session::get('tenant_id'));
        }

        return $this->company;
    }

    public function id(): ?int
    {
        return $this->getCompany()?->id;
    }

    public function clear(): void
    {
        $this->company = null;
        Session::forget('tenant_id');
    }
}
