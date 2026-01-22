<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\TenantService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $company = app(TenantService::class)->getCompany();
        // Check if subscribed
        $isSubscribed = $user->subscription_ends_at && $user->subscription_ends_at->isFuture();

        $price = \App\Models\Setting::where('key', 'subscription_price')->value('value') ?? 5000;

        return view('subscription.index', [
            'company' => $company,
            'isSubscribed' => $isSubscribed,
            'subscriptionEndsAt' => $user->subscription_ends_at,
            'price' => $price,
        ]);
    }

    public function success()
    {
        return view('subscription.success');
    }

    public function create(Request $request)
    {
        $wallet = env('YOOMONEY_WALLET');

        if (!$wallet) {
            return redirect()->back()->with('error', 'Настройки оплаты не сконфигурированы (YOOMONEY).');
        }

        $amount = (float) (\App\Models\Setting::where('key', 'subscription_price')->value('value') ?? 5000.00);

        // Create local payment record
        $localPayment = Payment::create([
            'user_id' => auth()->id(),
            'company_id' => null, // Payment is linked to user, not company
            'amount' => $amount,
            'period_months' => 1,
            'payment_system' => 'yoomoney',
            'status' => 'pending',
        ]);

        $label = $localPayment->id; // Use Payment ID as label for correlation
        $successUrl = route('subscription.callback');

        // Generate YooMoney QuickPay Form URL (using redirect to form)
        // Or we can return a view with autosubmit form. 
        // Redirect with query params is easiest for GET method, but QuickPay works via POST for full params support.
        // Let's create a temporary hidden form view or redirect with GET params if supported. 
        // YooMoney QuickPay supports GET.

        $params = [
            'receiver' => $wallet,
            'quickpay-form' => 'shop',
            'targets' => 'Подписка eJydo ' . auth()->user()->phone,
            'paymentType' => 'AC', // Bank Card
            'sum' => $amount,
            'label' => $label,
            'successURL' => $successUrl,
        ];

        $redirectUrl = 'https://yoomoney.ru/quickpay/confirm.xml?' . http_build_query($params);

        return redirect($redirectUrl);
    }

    public function callback(Request $request)
    {
        // User redirected back from YooMoney.
        // We can't verify payment here without API token. 
        // We rely on Webhook. 
        // Just show a "Processing" message.
        return redirect()->route('subscription.index')->with('info', 'Платёж обрабатывается. Если вы успешно оплатили, подписка активируется в течение нескольких минут.');
    }

    public function webhook(Request $request)
    {
        $secret = env('YOOMONEY_SECRET');

        try {
            // YooMoney sends form-data (application/x-www-form-urlencoded)
            $notification_type = $request->input('notification_type');
            $operation_id = $request->input('operation_id');
            $amount = $request->input('amount');
            $currency = $request->input('currency');
            $datetime = $request->input('datetime');
            $sender = $request->input('sender');
            $codepro = $request->input('codepro');
            $label = $request->input('label'); // This is our Payment ID
            $sha1_hash = $request->input('sha1_hash');

            // Validation Logic
            // sha1_hash = SHA1(notification_type & operation_id & amount & currency & datetime & sender & codepro & notification_secret & label)

            $chain = implode('&', [
                $notification_type,
                $operation_id,
                $amount,
                $currency,
                $datetime,
                $sender,
                $codepro,
                $secret,
                $label
            ]);

            $calculatedHash = sha1($chain);

            if ($calculatedHash !== $sha1_hash) {
                Log::warning('YooMoney Webhook Hash Mismatch', ['calculated' => $calculatedHash, 'received' => $sha1_hash]);
                return response('Hash mismatch', 400);
            }

            // Find payment by its ID (label)
            $payment = Payment::find($label);
            if (!$payment) {
                Log::error('YooMoney Webhook: Payment not found for label: ' . $label);
                return response('Payment not found', 200);
            }

            // Check codepro (if true, payment is protected, we can't accept it yet? Usually false for simple transfers)
            if ($codepro === 'true' || $codepro === true) {
                Log::warning('YooMoney Webhook: CodePro is true, ignoring.');
                return response('CodePro not supported', 200);
            }

            if ($payment->status !== 'completed') {
                $payment->update([
                    'status' => 'completed',
                    'transaction_id' => $operation_id, // Store YooMoney Operation ID
                    'paid_at' => now(),
                ]);

                // Extend subscription
                $user = $payment->user;
                if ($user) {
                    $currentExpires = $user->subscription_ends_at;
                    // Ensure Carbon object
                    if (is_string($currentExpires)) {
                        $currentExpires = \Illuminate\Support\Facades\Date::parse($currentExpires);
                    }

                    if ($currentExpires && $currentExpires->isFuture()) {
                        $newExpires = $currentExpires->copy()->addDays(30);
                    } else {
                        $newExpires = now()->addDays(30);
                    }
                    $user->update(['subscription_ends_at' => $newExpires]);
                }
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('YooMoney Webhook Error: ' . $e->getMessage());
            return response('Error', 500);
        }
    }
}
