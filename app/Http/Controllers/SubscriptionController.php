<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\TenantService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use YooKassa\Client;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $company = app(TenantService::class)->getCompany();
        // Check if subscribed
        $isSubscribed = $user->subscription_ends_at && $user->subscription_ends_at->isFuture();

        return view('subscription.index', compact('company', 'isSubscribed'));
    }

    public function success()
    {
        return view('subscription.success');
    }

    public function create(Request $request)
    {
        $company = app(TenantService::class)->getCompany();
        if (!$company) {
            return redirect()->back()->with('error', 'Выберите компанию для оплаты.');
        }

        $shopId = env('YOOKASSA_SHOP_ID');
        $secretKey = env('YOOKASSA_SECRET_KEY');

        if (!$shopId || !$secretKey) {
            return redirect()->back()->with('error', 'Настройки оплаты не сконфигурированы (YOOKASSA).');
        }

        $client = new Client();
        $client->setAuth($shopId, $secretKey);

        $amount = 5000.00;
        $description = 'Уплата подписки eJudo (30 дней) для пользователя ' . auth()->user()->phone;

        // Create local payment record
        $localPayment = Payment::create([
            'user_id' => auth()->id(),
            'company_id' => $company->id,
            'amount' => $amount,
            'period_months' => 1,
            'payment_system' => 'yookassa',
            'status' => 'pending',
        ]);

        try {
            $response = $client->createPayment(
                [
                    'amount' => [
                        'value' => $amount,
                        'currency' => 'RUB',
                    ],
                    'confirmation' => [
                        'type' => 'redirect',
                        'return_url' => route('subscription.callback'),
                    ],
                    'capture' => true,
                    'description' => $description,
                    'metadata' => [
                        'company_id' => $company->id,
                        'payment_id' => $localPayment->id,
                    ],
                ],
                uniqid('', true) // Idempotence Key
            );

            // Update local payment with external ID
            $localPayment->update([
                'transaction_id' => $response->getId(),
            ]);

            // Redirect user to YooKassa
            return redirect($response->getConfirmation()->getConfirmationUrl());

        } catch (\Exception $e) {
            Log::error('YooKassa Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ошибка при создании платежа: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        // Actually, we should capture the payment status. 
        // YooKassa redirects here. We can check the status from API using our local pending payments or request params if present (usually we just check the latest pending payment or use webhook. But for simplicity let's check the latest pending payment for this user/company or look for a param?).
        // YooKassa doesn't always send paymentId in GET params on return_url. It depends.
        // But we can check the most recent pending payment for this company.

        $company = app(TenantService::class)->getCompany();
        if (!$company) {
            return redirect()->route('subscription.index')->with('error', 'Company context lost.');
        }

        $shopId = env('YOOKASSA_SHOP_ID');
        $secretKey = env('YOOKASSA_SECRET_KEY');
        $client = new Client();
        $client->setAuth($shopId, $secretKey);

        // Find latest pending payment
        $payment = Payment::where('company_id', $company->id)
            ->where('status', 'pending')
            ->whereNotNull('transaction_id')
            ->latest()
            ->first();

        if (!$payment) {
            // Maybe already processed?
            return redirect()->route('subscription.index');
        }

        try {
            $paymentInfo = $client->getPaymentInfo($payment->transaction_id);

            if ($paymentInfo->getStatus() === 'succeeded') {
                $payment->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);

                // Extend subscription
                $user = $payment->user;
                $currentExpires = $user->subscription_ends_at;
                if ($currentExpires && $currentExpires->isFuture()) {
                    $newExpires = $currentExpires->copy()->addDays(30);
                } else {
                    $newExpires = now()->addDays(30);
                }

                $user->update(['subscription_ends_at' => $newExpires]);

                return redirect()->route('payment.success');
            } elseif ($paymentInfo->getStatus() === 'canceled') {
                $payment->update(['status' => 'failed']);
                return redirect()->route('subscription.index')->with('error', 'Оплата была отменена.');
            } else {
                // still pending
                return redirect()->route('subscription.index')->with('info', 'Оплата в обработке. Обновите страницу через минуту.');
            }

        } catch (\Exception $e) {
            Log::error('YooKassa Check Error: ' . $e->getMessage());
            return redirect()->route('subscription.index')->with('error', 'Ошибка проверки статуса платежа.');
        }
    }

    public function webhook(Request $request)
    {
        $shopId = env('YOOKASSA_SHOP_ID');
        $secretKey = env('YOOKASSA_SECRET_KEY');

        try {
            $source = file_get_contents('php://input');
            $requestBody = json_decode($source, true);

            if (!$requestBody) {
                return response('Invalid Request', 400);
            }

            $factory = new \YooKassa\Model\Notification\NotificationFactory();
            $notification = $factory->factory($requestBody);
            $paymentObject = $notification->getObject();

            $paymentId = $paymentObject->getId();

            // Find local payment
            $payment = Payment::where('transaction_id', $paymentId)->first();

            if (!$payment) {
                return response('Payment not found', 200);
            }

            if ($notification->getEvent() === \YooKassa\Model\Notification\NotificationEventType::PAYMENT_SUCCEEDED) {
                if ($payment->status !== 'completed') {
                    $payment->update([
                        'status' => 'completed',
                        'paid_at' => now(),
                    ]);

                    // Extend subscription
                    $user = $payment->user;
                    if ($user) {
                        $currentExpires = $user->subscription_ends_at;
                        if ($currentExpires && $currentExpires->isFuture()) {
                            $newExpires = $currentExpires->copy()->addDays(30);
                        } else {
                            $newExpires = now()->addDays(30);
                        }
                        $user->update(['subscription_ends_at' => $newExpires]);
                    }
                }
            } elseif ($notification->getEvent() === \YooKassa\Model\Notification\NotificationEventType::PAYMENT_CANCELED) {
                $payment->update(['status' => 'failed']);
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('YooKassa Webhook Error: ' . $e->getMessage());
            return response('Error', 500);
        }
    }
}
