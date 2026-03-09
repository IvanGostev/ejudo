<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoService
{
    protected $apiKey;
    protected $baseUrl = 'https://checko.ru/api/v2/company';

    public function __construct()
    {
        $this->apiKey = config('services.checko.key');
    }

    /**
     * Поиск компании по ИНН.
     * 
     * @param string $inn
     * @return array|null
     */
    public function findByInn(string $inn)
    {
        if (!$this->apiKey) {
            Log::error('Checko API key is not set');
            return null;
        }

        try {
            $response = Http::get($this->baseUrl, [
                'key' => $this->apiKey,
                'inn' => $inn,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['data'])) {
                    $item = $data['data'];
                    $fullName = $item['наименование_полное'] ?? $item['наименование_краткое'] ?? '';
                    
                    // Определение типа организации
                    $type = '';
                    if (mb_stripos($fullName, 'Индивидуальный предприниматель') !== false) {
                        $type = 'ИП';
                    } elseif (mb_stripos($fullName, 'Общество с ограниченной ответственностью') !== false) {
                        $type = 'ООО';
                    } elseif (mb_stripos($fullName, 'Публичное акционерное общество') !== false) {
                        $type = 'ПАО';
                    } elseif (mb_stripos($fullName, 'Акционерное общество') !== false) {
                        $type = 'АО';
                    } elseif (mb_stripos($fullName, 'Муниципальное унитарное предприятие') !== false) {
                        $type = 'МУП';
                    }

                    return [
                        'name'    => $fullName,
                        'type'    => $type,
                        'inn'     => $item['инн'] ?? '',
                        'kpp'     => $item['кпп'] ?? '',
                        'ogrn'    => $item['огрн'] ?? '',
                        'address' => $item['адрес_регистрации'] ?? '',
                    ];
                }
            }
            
            Log::warning('Checko API response error: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            Log::error('Checko API exception: ' . $e->getMessage());
            return null;
        }
    }
}
