<?php

namespace App\Services;

use App\Models\UserCompany;
use Illuminate\Support\Facades\Auth;
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
            if ($this->company) {
                return $this->company;
            }
        }

        // Сессия истекла, но пользователь залогинен через "Запомнить меня" —
        // восстанавливаем компанию из пользователя и сразу сохраняем в сессию.
        if (Auth::check()) {
            $this->company = Auth::user()->companies()->first();
            if ($this->company) {
                Session::put('tenant_id', $this->company->id);
            }
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
