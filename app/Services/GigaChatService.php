<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GigaChatService
{
    protected string $authUrl = 'https://ngw.devices.sberbank.ru:9443/api/v2/oauth';
    protected string $baseUrl = 'https://gigachat.devices.sberbank.ru/api/v1';
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $this->clientId = env('GIGACHAT_CLIENT_ID');
        $this->clientSecret = env('GIGACHAT_CLIENT_SECRET');
    }

    protected function getAccessToken(): ?string
    {
        // Check cache first to avoid rate limits
        if (cache()->has('gigachat_token')) {
            return cache()->get('gigachat_token');
        }

        $authKey = env('GIGACHAT_AUTH_KEY');
        $scope = env('GIGACHAT_SCOPE', 'GIGACHAT_API_PERS');

        // Need to disable SSL verification for development environments often with GigaChat/Sber certs
        // or ensure the Russian CA certificates are installed. 
        // For this environment, we use 'verify' => false cautiously.

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . $authKey,
            'RqUID' => (string) Str::uuid(),
            'Content-Type' => 'application/x-www-form-urlencoded'
        ])
            ->withOptions(['verify' => false])
            ->asForm()
            ->post($this->authUrl, [
                    'scope' => $scope
                ]);

        if ($response->successful()) {
            $token = $response->json('access_token');
            $expiresAt = $response->json('expires_at'); // Unix timestamp

            // Cache slightly less than expiration
            // If expires_at is milliseconds, convert. Usually it's milliseconds from epoch.
            // Assuming 30 minutes safe cache.
            cache()->put('gigachat_token', $token, 1700);

            return $token;
        }

        Log::error('GigaChat Auth Failed: ' . $response->body());
        return null;
    }

    public function uploadFile(string $filePath): string
    {
        $token = $this->getAccessToken();

        if (!file_exists($filePath)) {
            throw new \Exception("File not found for upload: $filePath");
        }

        $response = Http::withToken($token)
            ->withOptions(['verify' => false])
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post($this->baseUrl . '/files', [
                    'purpose' => 'general'
                ]);

        if ($response->failed()) {
            throw new \Exception('GigaChat File Upload Failed: ' . $response->body());
        }

        return $response->json('id');
    }

    public function extractJsonFromAct(string $filePath): array
    {
        $token = $this->getAccessToken();

        if (!$token) {
            throw new \Exception('GigaChat Auth Token Error. Check credentials.');
        }

        try {
            // 1. Upload File
            $fileId = $this->uploadFile($filePath);

            $prompt = "Проанализируй этот документ (Акт выполненных работ) и извлеки данные.
            
ВАЖНЫЕ ПРАВИЛА:
1. Ищи Код ФККО в тексте или таблице. Это обычно 11 цифр (например 4 34 110 02 29 5).
2. Количество (quantity) должно быть числом (float). Запятые (0,15) меняй на точки (0.15).
3. Класс опасности (hazard_class) — последняя цифра кода.
4. ДАТА (date) ДОЛЖНА БЫТЬ В ФОРМАТЕ YYYY-MM-DD (например 2025-06-30). Если в тексте '30 июня 2025', конвертируй в '2025-06-30'.
5. 'provider' — Исполнитель (Подрядчик), кто оказывает услугу. 'receiver' — Заказчик, кто платит.
6. Для каждого пункта определи 'operation_type' (вид обращения): Транспортирование, Утилизация, Обезвреживание, Захоронение. Если явно сказано 'прием на утилизацию', то 'Утилизация'. По умолчанию 'Транспортирование'.

7. Ищи номер и дату договора (обычно после слов 'Договор №' ... 'от' ...). Извлеки строку вида '104/ХФЗТ/24 от 01.10.2024' и положи в поле 'number'. Это ВАЖНО: поле 'number' должно содержать именно номер и дату договора. Если договора нет, ищи номер акта.

Верни ТОЛЬКО валидный JSON с такой структурой:
{ 
    \"number\": \"...\", 
    \"date\": \"YYYY-MM-DD\", 
    \"provider\": \"...\", 
    \"receiver\": \"...\", 
    \"items\": [ 
        { 
            \"name\": \"...\", 
            \"quantity\": 0.0, 
            \"unit\": \"...\", 
            \"fkko_code\": \"...\", 
            \"hazard_class\": \"...\",
            \"operation_type\": \"...\"
        } 
    ] 
}";

            $response = Http::withToken($token)
                ->withOptions(['verify' => false])
                ->post($this->baseUrl . '/chat/completions', [
                        'model' => 'GigaChat', // Or GigaChat-Pro if available/paid
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $prompt,
                                'attachments' => [$fileId]
                            ]
                        ],
                        'temperature' => 0.1
                    ]);

            if ($response->failed()) {
                throw new \Exception('GigaChat API Request Failed: ' . $response->body());
            }

            $content = $response->json('choices.0.message.content');

            // Clean markdown jsons
            $content = str_replace(['```json', '```'], '', $content);
            $content = trim($content);

            $json = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('GigaChat returned invalid JSON: ' . json_last_error_msg() . ' Content: ' . $content);
            }

            return $json ?? [];

        } catch (\Exception $e) {
            Log::error('GigaChat Exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
