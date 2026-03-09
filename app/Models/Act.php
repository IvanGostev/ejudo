<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Act extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'company_id',
        'act_type',
        'act_number',
        'contract_details',
        'filename',
        'original_name',
        'file_size',
        'act_data',
        'status',
        'processing_result',
    ];

    // Типы актов
    public const TYPES = [
        'transfer'      => 'Акт передачи',
        'processing'    => 'Акт обработки',
        'utilization'   => 'Акт утилизации',
        'neutralization'=> 'Акт обезвреживания',
        'storage'       => 'Акт хранения',
        'burial'        => 'Акт захоронения',
    ];

    public function getTypeLabel(): string
    {
        return self::TYPES[$this->act_type] ?? 'Акт передачи';
    }

    // Следующий сквозной номер для компании
    public static function nextActNumber(int $companyId): int
    {
        $max = static::where('company_id', $companyId)->max('act_number');
        return ($max ?? 0) + 1;
    }

    protected $casts = [
        'act_data' => 'array',
        'processing_result' => 'array',
    ];
}
