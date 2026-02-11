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

    public function create(Request $request, \App\Services\TinkoffPaymentService $tinkoff)
    {
        $amount = (float) (\App\Models\Setting::where('key', 'subscription_price')->value('value') ?? 5000.00);
        $payment = Payment::create([
            'user_id' => auth()->id(),
            'company_id' => null,
            'amount' => $amount,
            'period_months' => 1,
            'payment_system' => 'tbank',
            'status' => 'pending',
        ]);

        $response = $tinkoff->init($payment);

        if ($response && isset($response['PaymentURL'])) {
            $payment->update([
                'transaction_id' => $response['PaymentId']
            ]);

            return redirect($response['PaymentURL']);
        }

        Log::error('Tinkoff Init Error', ['response' => $response]);

        return redirect()->back()->with('error', 'Ошибка инициализации оплаты через Т-Банк.');
    }

    public function callback(Request $request)
    {
        return redirect()->route('subscription.index')->with('info', 'Платёж обрабатывается.');
    }

    public function webhook(Request $request, \App\Services\TinkoffPaymentService $tinkoff)
    {
        Log::info('TBank Webhook Received', $request->all());

        if (config('app.debug')) {
            try {
                \Illuminate\Support\Facades\Mail::raw(
                    "TBank Webhook Received (" . $request->method() . "):\n\n" .
                    "All: " . json_encode($request->all(), JSON_PRETTY_PRINT) . "\n\n" .
                    "Content: " . $request->getContent(),
                    function ($message) {
                        $message->to('ivangostev07@gmail.com')
                            ->subject('TBank Webhook Notification');
                    }
                );
            } catch (\Exception $e) {
                Log::error('Mail Error: ' . $e->getMessage());
            }
        }

        $paymentId = $request->input('PaymentId');
        $status = $request->input('Status');
        $orderId = $request->input('OrderId');
        $payment = Payment::find($orderId);
        if (!$payment) {
            Log::error('TBank Webhook: Payment not found for OrderId: ' . $orderId);
            return response('OK', 200);
        }
        if ($status === 'AUTHORIZED') {
            Log::info('Payment Authorized, Capturing...', ['PaymentId' => $paymentId]);
            $confirm = $tinkoff->confirm($paymentId);
            if ($confirm && isset($confirm['Success']) && $confirm['Success']) {
                if ($confirm['Status'] === 'CONFIRMED') {
                    $status = 'CONFIRMED';
                }
            } else {
                Log::error('Capture Failed', ['response' => $confirm]);
            }
        }

        if ($status === 'CONFIRMED' || $status === 'AUTHORIZED') {
            if ($payment->status !== 'completed') {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                    'transaction_id' => $paymentId ?? $payment->transaction_id
                ]);

                $user = $payment->user;
                if ($user) {
                    $currentExpires = $user->subscription_ends_at;
                    if (is_string($currentExpires)) {
                        $currentExpires = \Illuminate\Support\Facades\Date::parse($currentExpires);
                    }

                    if ($currentExpires && $currentExpires->isFuture()) {
                        $newExpires = $currentExpires->copy()->addDays(30);
                    } else {
                        $newExpires = now()->addDays(30);
                    }
                    $user->update(['subscription_ends_at' => $newExpires]);
                    Log::info('Subscription Extended', ['user_id' => $user->id]);
                    $this->processReferral($payment);
                }
            }
        } elseif (in_array($status, ['REJECTED', 'CANCELED'])) {
            $payment->update(['status' => 'failed']);
        }

        return response('OK', 200);
    }

    private function processReferral(Payment $payment)
    {
        $user = $payment->user;
        if (!$user || !$user->referrer_id) {
            return;
        }

        $referrer = $user->referrer;
        if (!$referrer) {
            return;
        }

        $referralPercent = (float) (\App\Models\Setting::where('key', 'referral_percent')->value('value') ?? 10.0);
        $earningAmount = round(($payment->amount * $referralPercent) / 100, 2);

        if ($earningAmount <= 0) {
            return;
        }
        \App\Models\ReferralEarning::create([
            'user_id' => $referrer->id,
            'referral_id' => $user->id,
            'payment_id' => $payment->id,
            'amount' => $earningAmount,
            'percent' => $referralPercent,
        ]);
        $referrer->increment('referral_balance', $earningAmount);

        Log::info('Referral Earning Processed', [
            'referrer_id' => $referrer->id,
            'referral_id' => $user->id,
            'amount' => $earningAmount
        ]);
    }
}
