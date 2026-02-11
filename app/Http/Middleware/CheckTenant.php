<?php

namespace App\Http\Middleware;

use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenant
{
    public function __construct(protected TenantService $tenantService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->tenantService->getCompany()) {

            $user = $request->user();
            if (!$user) {
                return redirect()->route('login');
            }

            $company = $user->companies()->first();
            if ($company) {
                $this->tenantService->setCompany($company);
            } else {

                if (!$request->routeIs('company.create') && !$request->routeIs('company.store')) {
                    return redirect()->route('company.create');
                }
            }
        }

        return $next($request);
    }
}
