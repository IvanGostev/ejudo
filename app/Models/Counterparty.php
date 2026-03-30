<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counterparty extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'inn',
        'kpp',
        'legal_address',
        'license_number',
        'license_valid_until',
    ];

    protected $casts = [
        'license_valid_until' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(UserCompany::class, 'company_id');
    }

    /**
     * Валидация контрольного числа ИНН (10 или 12 цифр).
     */
    public static function validateInn(string $inn): bool
    {
        $inn = preg_replace('/\D/', '', $inn);

        if (strlen($inn) === 10) {
            $weights = [2, 4, 10, 3, 5, 9, 4, 6, 8];
            $sum = 0;
            for ($i = 0; $i < 9; $i++) {
                $sum += (int)$inn[$i] * $weights[$i];
            }
            return ((int)$inn[9] === ($sum % 11) % 10);
        }

        if (strlen($inn) === 12) {
            $w1 = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
            $w2 = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
            $s1 = 0;
            for ($i = 0; $i < 10; $i++) $s1 += (int)$inn[$i] * $w1[$i];
            $s2 = 0;
            for ($i = 0; $i < 11; $i++) $s2 += (int)$inn[$i] * $w2[$i];
            return ((int)$inn[10] === ($s1 % 11) % 10) && ((int)$inn[11] === ($s2 % 11) % 10);
        }

        return false;
    }

    /**
     * Возвращает «снимок» реквизитов для сохранения в акте.
     */
    public function toSnapshot(): array
    {
        return [
            'name'                => $this->name,
            'inn'                 => $this->inn,
            'kpp'                 => $this->kpp,
            'legal_address'       => $this->legal_address,
            'license_number'      => $this->license_number,
            'license_valid_until' => $this->license_valid_until?->format('d.m.Y'),
        ];
    }
}
