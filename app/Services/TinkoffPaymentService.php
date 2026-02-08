<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TinkoffPaymentService
{
    protected $terminalId;
    protected $password;
    protected $apiUrl = 'https://securepay.tinkoff.ru/v2/';

    public function __construct()
    {
        $this->terminalId = config('services.tinkoff.terminal_id');
        $this->password = config('services.tinkoff.password');
    }

    /**
     * Initiate a payment
     *
     * @param mixed $payment Local payment model
     * @return array|null Response from Tinkoff or null on failure
     */
    public function init($payment)
    {
        $amountKopecks = (int) ($payment->amount * 100);

        // Create Receipt Data
        // Ideally Taxation should be configurable. Defaulting to 'usn_income' (Simplified DB).
        $receipt = [
            'Email' => $payment->user->email ?? 'unknown@example.com',
            'Taxation' => 'usn_income',
            'Items' => [
                [
                    'Name' => 'Подписка eJydo',
                    'Price' => $amountKopecks,
                    'Quantity' => 1.00,
                    'Amount' => $amountKopecks,
                    'Tax' => 'none',
                    'PaymentMethod' => 'full_prepayment',
                    'PaymentObject' => 'service',
                ]
            ]
        ];


        $params = [
            'TerminalKey' => $this->terminalId,
            'Amount' => $amountKopecks,
            'OrderId' => $payment->id . '_' . time(),
            'Description' => 'Подписка eJydo',
            'NotificationURL' => route('subscription.webhook'),
            // 'SuccessURL' => route('payment.success'), // Can be passed here or set in terminal settings
            // 'FailURL'    => route('payment.fail'),
            'Receipt' => $receipt
        ];

        // Add specific URLs if needed, often handy to override defaults
        $params['SuccessURL'] = route('subscription.index'); // Fallback/Standard
        if (\Route::has('subscription.success')) {
            $params['SuccessURL'] = route('subscription.success');
        } elseif (\Route::has('payment.success')) {
            $params['SuccessURL'] = route('payment.success');
        }

        $params['Token'] = $this->generateToken($params);

        try {
            $response = Http::post($this->apiUrl . 'Init', $params);
            $data = $response->json();

            if (!$data['Success']) {
                Log::error('Tinkoff Init Failed: ' . ($data['Message'] ?? 'Unknown error'), $data);
                return null;
            }

            return $data; // Contains PaymentURL, PaymentId
        } catch (\Exception $e) {
            Log::error('Tinkoff Init Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get Payment State
     * 
     * @param string $paymentId Tinkoff Payment ID
     * @return array|null
     */
    public function getState($paymentId)
    {
        $params = [
            'TerminalKey' => $this->terminalId,
            'PaymentId' => $paymentId,
        ];

        $params['Token'] = $this->generateToken($params);

        try {
            $response = Http::post($this->apiUrl . 'GetState', $params);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tinkoff GetState Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Confirm Payment (for 2-step payments)
     * 
     * @param string $paymentId Tinkoff Payment ID
     * @param int|null $amountKopecks Amount to confirm (optional)
     * @return array|null
     */
    public function confirm($paymentId, $amountKopecks = null)
    {
        $params = [
            'TerminalKey' => $this->terminalId,
            'PaymentId' => $paymentId,
        ];

        if ($amountKopecks) {
            $params['Amount'] = $amountKopecks;
        }

        $params['Token'] = $this->generateToken($params);

        try {
            $response = Http::post($this->apiUrl . 'Confirm', $params);
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Tinkoff Confirm Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate Token for request signatures
     */
    public function generateToken(array $args)
    {
        $args['Password'] = $this->password;
        ksort($args);

        $tokenStr = '';
        foreach ($args as $key => $value) {
            if (!in_array($key, ['Token', 'Receipt', 'Data'])) {
                if (is_bool($value)) {
                    $value = $value ? 'true' : 'false';
                }
                // Determine if we need to convert value to string? 
                // PHP does it implicitly but strict typing might matter.
                // Tinkoff expects string concatenation.
                $tokenStr .= (string) $value;
            }
        }

        return hash('sha256', $tokenStr);
    }

    /**
     * Validate incoming webhook request
     */
    public function checkToken(array $requestData)
    {
        if (!isset($requestData['Token'])) {
            return false;
        }

        $receivedToken = $requestData['Token'];

        // Remove Token from args to recalculate
        $args = $requestData;
        unset($args['Token']);

        $calculatedToken = $this->generateToken($args);

        if ($calculatedToken !== $receivedToken) {
            Log::warning('TBank Token Mismatch', [
                'received' => $receivedToken,
                'calculated' => $calculatedToken,
                'args_dump' => $args
            ]);
            return false;
        }

        return true;
    }
}
