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
        $request->validate(['email' => 'required|email']);

        $email = $request->email;
        // OTP Logic
        $code = (string) rand(1000, 9999);

        // Log the code for debugging/audit
        \Illuminate\Support\Facades\Log::info("Auth code for {$email}: {$code}");

        // Send Email
        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\AuthCodeMail($code));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send auth email to {$email}: " . $e->getMessage());
            // Continue flow even if email fails, since we logged the code.
            // valid for dev/staging or when SMTP has issues but we want to allow login.
        }

        // Store in cache for 5 mins
        Cache::put('auth_code_' . $email, $code, 300);

        $response = ['message' => 'Код отправлен на ваш Email'];

        return response()->json($response);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
        ]);

        $email = $request->email;
        $cachedCode = Cache::get('auth_code_' . $email);

        if (!$cachedCode || $cachedCode !== $request->code) {
            throw ValidationException::withMessages(['code' => 'Неверный или истекший код']);
        }

        // Logic: Find or create user
        $user = User::where('email', $email)->first();

        $isNewUser = false;
        if (!$user) {
            // New User Flow
            $user = User::create([
                'email' => $email,
                'phone_verified' => false, // Not relevant anymore, or keep as false
            ]);
            $isNewUser = true;
        }

        Cache::forget('auth_code_' . $email);
        Auth::login($user);

        // Auto-select company if exists
        $company = $user->companies()->first();
        if ($company) {
            $this->tenantService->setCompany($company);
        }

        $redirectUrl = route('dashboard');
        if ($isNewUser) {
            $redirectUrl = route('instruction.index');
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
