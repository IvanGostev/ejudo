<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserCompany;
use App\Services\SmsService;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        protected SmsService $smsService,
        protected TenantService $tenantService
    ) {
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function sendCode(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|min:10']);

        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        // OTP Logic
        $code = (string) rand(1000, 9999);

        // In local/testing, maybe don't actually send SMS every time if needed, or stick to prod logic.
        // For MVP we just send it.
        if (!app()->isLocal()) {
            try {
                $this->smsService->send($phone, "Код подтверждения: $code");
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        // Store in cache for 5 mins
        Cache::put('sms_code_' . $phone, $code, 300);

        $response = ['message' => 'Код отправлен'];

        if (app()->isLocal()) {
            $response['debug_code'] = $code;
        }


        return response()->json($response);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'code' => 'required|string',
        ]);

        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        $cachedCode = Cache::get('sms_code_' . $phone);

        if (!$cachedCode || $cachedCode !== $request->code) {
            throw ValidationException::withMessages(['code' => 'Неверный или истекший код']);
        }

        // Logic: Find or create user
        $user = User::where('phone', $phone)->first();

        $isNewUser = false;
        if (!$user) {
            // New User Flow
            $user = User::create([
                'phone' => $phone,
                'phone_verified' => true,
            ]);
            $isNewUser = true;
        } else {
            $user->update(['phone_verified' => true]);
            $isNewUser = false;
        }

        Cache::forget('sms_code_' . $phone);
        Auth::login($user);

        // Auto-select company if exists
        $company = $user->companies()->first();
        if ($company) {
            $this->tenantService->setCompany($company);
        }

        $redirectUrl = route('dashboard');
        if ($isNewUser) {
            $redirectUrl = route('instruction.index');
        } elseif (!$company) {
            $redirectUrl = route('company.create');
        }

        return response()->json(['message' => 'Logged in', 'user' => $user, 'company' => $company, 'redirect_url' => $redirectUrl]);
    }

    public function showCompanyCreate()
    {
        return view('auth.company-create');
    }

    public function registerCompany(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Check limit
        $subscriptionEnds = $user->subscription_ends_at;
        if (is_string($subscriptionEnds)) {
            $subscriptionEnds = \Illuminate\Support\Facades\Date::parse($subscriptionEnds);
        }
        $isSubscribed = $subscriptionEnds && $subscriptionEnds->isFuture();
        if (!$isSubscribed && $user->companies()->count() >= 1) {
            return response()->json(['message' => 'Без активной подписки можно создать только одну компанию.'], 403);
        }

        $request->validate([
            'name' => 'required|string',
            'inn' => 'required|string|size:10,12', // 10 for Juridical, 12 for IP (though strict validation varies)
            'type' => 'required|in:ООО,ИП',
            'ogrn' => 'required|string',
            'legal_address' => 'required|string',
        ]);

        $user = Auth::user();

        $company = $user->companies()->create([
            'name' => $request->name,
            'inn' => $request->inn,
            'type' => $request->type,
            'ogrn' => $request->ogrn,
            'legal_address' => $request->legal_address,
            'is_active' => true,
        ]);

        $this->tenantService->setCompany($company);

        return response()->json(['message' => 'Company created', 'company' => $company]);
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
