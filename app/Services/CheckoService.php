<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckoService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.checko.ru/v2/company';

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

            Log::info('Checko API raw response for INN ' . $inn . ': ' . $response->body());

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['data'])) {
                    $item = $data['data'];

                    $fullName = $item['НаимПолн'] ?? $item['НаимСокр'] ?? '';


                    $type = '';
                    $okopfName = mb_strtolower($item['ОКОПФ']['Наим'] ?? '');
                    $shortName = $item['НаимСокр'] ?? '';
                    $fullName = $item['НаимПолн'] ?? '';

                    $knownTypes = [
                        'ИП', 'ООО', 'ПАО', 'АО', 'ЗАО', 'ОАО',
                        'МУП', 'ГУП', 'ФГУП',
                        'МБУ', 'ГБУ', 'ФГБУ', 'МАУ', 'ГАУ', 'ФГАУ', 'МКУ', 'ГКУ', 'ФКУ',
                        'АНО', 'ТСЖ', 'ТСН', 'СНТ', 'ДНТ', 'КФХ',
                        'МБДОУ', 'МАДОУ', 'МБОУ', 'МАОУ', 'ГБОУ', 'ГКДОУ', 'ГКОУ',
                        'ЧОУ', 'НОУ', 'ПК', 'ПО'
                    ];


                    if ($shortName) {
                        $shortNameClean = trim(preg_replace('/["\'«»]/', '', $shortName));
                        $parts = explode(' ', $shortNameClean);
                        if (count($parts) > 0) {
                            $firstWord = mb_strtoupper($parts[0]);
                            if (in_array($firstWord, $knownTypes)) {
                                $type = $firstWord;
                            } else {
                                $lastWord = mb_strtoupper(end($parts));
                                if (in_array($lastWord, $knownTypes)) {
                                    $type = $lastWord;
                                }
                            }
                        }
                    }


                    if (!$type) {
                        if (mb_strpos($okopfName, 'индивидуальные предприниматели') !== false || mb_stripos($fullName, 'Индивидуальный предприниматель') !== false || mb_stripos($fullName, 'ИП ') === 0) {
                            $type = 'ИП';
                        } elseif (mb_strpos($okopfName, 'общества с ограниченной ответственностью') !== false || mb_stripos($fullName, 'Общество с ограниченной ответственностью') !== false) {
                            $type = 'ООО';
                        } elseif (mb_strpos($okopfName, 'публичные акционерные общества') !== false || mb_stripos($fullName, 'Публичное акционерное общество') !== false) {
                            $type = 'ПАО';
                        } elseif (mb_strpos($okopfName, 'непубличные акционерные общества') !== false || mb_strpos($okopfName, 'закрытые акционерные общества') !== false || mb_stripos($fullName, 'Акционерное общество') !== false) {
                            $type = 'АО';
                        } elseif (mb_strpos($okopfName, 'федеральные государственные унитарные предприятия') !== false || mb_stripos($fullName, 'федеральное государственное унитарное предприятие') !== false) {
                            $type = 'ФГУП';
                        } elseif (mb_strpos($okopfName, 'муниципальные унитарные предприятия') !== false) {
                            $type = 'МУП';
                        } elseif (mb_strpos($okopfName, 'государственные унитарные предприятия') !== false) {
                            $type = 'ГУП';
                        } elseif (mb_strpos($okopfName, 'автономные некоммерческие организации') !== false) {
                            $type = 'АНО';
                        } elseif (mb_strpos($okopfName, 'товарищества собственников жилья') !== false) {
                            $type = 'ТСЖ';
                        } elseif (mb_strpos($okopfName, 'бюджетные учреждения') !== false) {
                            if (mb_stripos($fullName, 'федеральное') !== false) $type = 'ФГБУ';
                            elseif (mb_stripos($fullName, 'муниципальное') !== false) $type = 'МБУ';
                            else $type = 'ГБУ';
                        } elseif (mb_strpos($okopfName, 'казенные учреждения') !== false) {
                            if (mb_stripos($fullName, 'федеральное') !== false) $type = 'ФКУ';
                            elseif (mb_stripos($fullName, 'муниципальное') !== false) $type = 'МКУ';
                            else $type = 'ГКУ';
                        } elseif (mb_strpos($okopfName, 'автономные учреждения') !== false) {
                            if (mb_stripos($fullName, 'федеральное') !== false) $type = 'ФГАУ';
                            elseif (mb_stripos($fullName, 'муниципальное') !== false) $type = 'МАУ';
                            else $type = 'ГАУ';
                        }
                    }


                    $licenseDetails = '';
                    if (isset($item['Лиценз']) && is_array($item['Лиценз'])) {
                        foreach ($item['Лиценз'] as $lic) {
                            $isWasteLicense = false;
                            if (isset($lic['ВидДеят']) && is_array($lic['ВидДеят'])) {
                                foreach ($lic['ВидДеят'] as $activity) {
                                    if (mb_stripos($activity, 'отход') !== false) {
                                        $isWasteLicense = true;
                                        break;
                                    }
                                }
                            }

                            if ($isWasteLicense || $licenseDetails === '') {
                                $num = $lic['Номер'] ?? '';
                                $date = $lic['ДатаНач'] ?? $lic['Дата'] ?? '';
                                if ($date) {
                                    $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
                                    if ($dateObj) {
                                        $date = $dateObj->format('d.m.Y');
                                    }
                                }
                                $org = $lic['ЛицОрг'] ?? '';
                                $licenseValidUntil = $lic['ДатаКон'] ?? null;

                                $licenseDetails = trim(sprintf("№ %s от %s, выд. %s", $num, $date, $org));

                                if ($isWasteLicense) {
                                    break;
                                }
                            }
                        }
                    }

                    $contactPerson = '';
                    if (!empty($item['Руковод']) && is_array($item['Руковод'])) {
                        $contactPerson = current($item['Руковод'])['ФИО'] ?? '';
                    }

                    $phone = '';
                    if (!empty($item['Контакты']['Тел']) && is_array($item['Контакты']['Тел'])) {
                        $phone = current($item['Контакты']['Тел']);
                    }

                    $email = '';
                    if (!empty($item['Контакты']['Емэйл']) && is_array($item['Контакты']['Емэйл'])) {
                        $email = current($item['Контакты']['Емэйл']);
                    }

                    return [
                        'name'    => $fullName,
                        'type'    => $type,
                        'inn'     => $item['ИНН'] ?? '',
                        'kpp'     => $item['КПП'] ?? '',
                        'ogrn'    => $item['ОГРН'] ?? '',
                        'address' => $item['ЮрАдрес']['АдресРФ'] ?? '',
                        'license_details' => $licenseDetails,
                        'license_valid_until' => $licenseValidUntil,
                        'contact_person' => $contactPerson,
                        'phone' => $phone,
                        'email' => $email,
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
