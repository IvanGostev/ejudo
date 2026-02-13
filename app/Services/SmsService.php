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

            $phone = preg_replace('/[^0-9]/', '', $phone);
            if (str_starts_with($phone, '8')) {
                $phone = '7' . substr($phone, 1);
            }
            $passwordHash = md5($this->password);
            $queryString = http_build_query([
                'phone' => $phone,
                'text' => $message,
                'sender' => $this->sender,
            ], '', '&', PHP_QUERY_RFC3986);

            $url = 'http://api.smsfeedback.ru/messages/v2/send/?' . $queryString;

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->login . ':' . $passwordHash)
            ])->get($url);

            $responseBody = trim($response->body());

            if ($response->successful()) {

                if (str_starts_with($responseBody, 'accepted')) {
                    Log::info("СМС отправлено {$phone}: {$message}. Ответ: " . $responseBody);
                    return true;
                }
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
                Log::error("СМС API Ошибка: " . $responseBody);
                throw new \Exception("СМС Шлюз Ошибка: " . $responseBody);
            }

            Log::error("СМС не отправлено {$phone}. Статус: " . $response->status());
            throw new \Exception("СМС HTTP Ошибка: " . $response->status() . " " . $responseBody);
        } catch (\Exception $e) {
            Log::error("СМС Исключение: " . $e->getMessage());

            throw $e;
        }
    }
}
