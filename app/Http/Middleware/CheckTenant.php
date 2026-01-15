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
            // If user has companies, select first (fallback logic already in AuthController, but good to have here too)
            // If no companies, redirect to creation page.

            $user = $request->user();
            if (!$user) {
                return redirect()->route('login');
            }

            $company = $user->companies()->first();
            if ($company) {
                $this->tenantService->setCompany($company);
            } else {
                // Allow access to company creation route
                if (!$request->routeIs('company.create') && !$request->routeIs('company.store')) {
                    return redirect()->route('company.create');
                }
            }
        }

        return $next($request);
    }
}
