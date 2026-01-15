<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $login;
    protected string $password;
    protected string $sender;
    protected string $baseUrl = 'http://api.smsfeedback.ru/messages/v2/send/';

    public function __construct()
    {
        $this->login = config('services.sms.login', env('SMS_LOGIN'));
        $this->password = config('services.sms.password', env('SMS_PASSWORD'));
        $this->sender = config('services.sms.sender', env('SMS_SENDER', 'TEST-SMS'));
    }

    public function send(string $phone, string $message): bool
    {
        try {
            // Normalize phone: 79991234567
            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($phone, '8')) {
                $phone = '7' . substr($phone, 1);
            }

            // Legacy Logic Adaptation
            // 1. MD5 the password (as per example)
            // Note: If env already contains raw password, we md5 it.
            // If the user already provided MD5 in env, this might double hash. 
            // Assuming env has RAW password based on typical usage.
            $passwordHash = md5($this->password);

            // 2. Construct Query Params using manual URL construction to match legacy encoding (RFC 3986 preferred)
            $queryString = http_build_query([
                'phone' => $phone,
                'text' => $message,
                'sender' => $this->sender,
            ], '', '&', PHP_QUERY_RFC3986); // Forces %20 for spaces

            $url = 'http://api.smsfeedback.ru/messages/v2/send/?' . $queryString;

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->login . ':' . $passwordHash)
            ])->get($url);

            $responseBody = trim($response->body());

            if ($response->successful()) {
                // Example response: "accepted;A133541BC"
                if (str_starts_with($responseBody, 'accepted')) {
                    Log::info("СМС отправлено {$phone}: {$message}. Ответ: " . $responseBody);
                    return true;
                }

                // Specific Error Mapping
                if ($responseBody === 'not enough credits') {
                    Log::error("СМС Ошибка: Недостаточно денег на балансе");
                    throw new \Exception("Недостаточно денег на балансе для отправки смс");
                }

                if ($responseBody === 'invalid mobile phone') {
                    Log::error("СМС Ошибка: Неверный формат номера");
                    throw new \Exception("Неверный формат номера телефона, проверка на стороне смс шлюза");
                }

                if (str_contains($responseBody, 'sender address invalid')) {
                    throw new \Exception("Неверное имя отправителя (Sender ID). Попробуйте 'TEST-SMS'.");
                }

                // General Fallback
                Log::error("СМС API Ошибка: " . $responseBody);
                throw new \Exception("СМС Шлюз Ошибка: " . $responseBody);
            }

            Log::error("СМС не отправлено {$phone}. Статус: " . $response->status());
            throw new \Exception("СМС HTTP Ошибка: " . $response->status() . " " . $responseBody);
        } catch (\Exception $e) {
            Log::error("СМС Исключение: " . $e->getMessage());
            // Re-throw if it's one of our friendly exceptions, otherwise default
            throw $e;
        }
    }
}
