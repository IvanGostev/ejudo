<?php

namespace App\Filament\Resources\FkkoCodeResource\Pages;

use App\Filament\Resources\FkkoCodeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use App\Models\FkkoCode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class ListFkkoCodes extends ListRecords
{
    protected static string $resource = FkkoCodeResource::class;
    
    private function formatFkko($code)
    {
        $clean = str_replace(' ', '', (string)$code);
        if (strlen($clean) === 11) {
            return substr($clean, 0, 1) . ' ' .
                   substr($clean, 1, 2) . ' ' .
                   substr($clean, 3, 3) . ' ' .
                   substr($clean, 6, 2) . ' ' .
                   substr($clean, 8, 2) . ' ' .
                   substr($clean, 10, 1);
        }
        return $code;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Экспорт в Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle('ФККО');

                    // Заголовки
                    $headers = [
                        'Код ФККО',
                        'Наименование',
                        'Класс опасности',
                        'Происхождение или условия образования',
                        'Агрегатное состояние и физическая форма',
                        'Химический и (или) компонентный состав, %',
                    ];
                    foreach ($headers as $i => $header) {
                        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
                        $sheet->setCellValue($col . '1', $header);
                        $sheet->getStyle($col . '1')->getFont()->setBold(true);
                        $sheet->getStyle($col . '1')->getFill()
                            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('D9E1F2');
                    }

                    // Данные
                    $codes = FkkoCode::orderBy('code')->get();
                    $row = 2;
                    foreach ($codes as $fkko) {
                        $formattedCode = $this->formatFkko($fkko->code);
                        $sheet->getCell('A' . $row)->setValueExplicit(
                            (string) $formattedCode,
                            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                        );
                        $sheet->setCellValue('B' . $row, $fkko->name);
                        $sheet->setCellValue('C' . $row, $fkko->hazard_class);
                        $sheet->setCellValue('D' . $row, $fkko->origin ?? '');
                        $sheet->setCellValue('E' . $row, $fkko->aggregate_state ?? '');
                        $sheet->setCellValue('F' . $row, $fkko->chemical_composition ?? '');
                        $row++;
                    }

                    // Автоширина колонок
                    foreach(['A','B','C','D','E','F'] as $col) {
                        $sheet->getColumnDimension($col)->setAutoSize(true);
                    }

                    $filename = 'ФККО_справочник_' . now()->format('Y-m-d') . '.xlsx';
                    $tmpFile = tempnam(sys_get_temp_dir(), 'fkko_');
                    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                    $writer->save($tmpFile);

                    return response()->download($tmpFile, $filename, [
                        'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])->deleteFileAfterSend(true);
                }),

            Actions\Action::make('importData')
                ->label('Импорт из Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->modalHeading('Импорт ФККО')
                ->modalWidth('4xl')
                ->form([
                    Forms\Components\Placeholder::make('example')
                        ->label('Пример структуры Excel')
                        ->content(new \Illuminate\Support\HtmlString('
                            <table class="w-full text-xs text-center border-collapse border border-gray-300 dark:border-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th style="text-align: center;" class="border border-gray-300 dark:border-gray-700 p-1">Код</th>
                                        <th style="text-align: center;" class="border border-gray-300 dark:border-gray-700 p-1">Наименование</th>
                                        <th style="text-align: center;" class="border border-gray-300 dark:border-gray-700 p-1">Класс</th>
                                        <th style="text-align: center;" class="border border-gray-300 dark:border-gray-700 p-1">Происхождение</th>
                                        <th style="text-align: center;" class="border border-gray-300 dark:border-gray-700 p-1">Состояние</th>
                                        <th style="text-align: center;" class="border border-gray-300 dark:border-gray-700 p-1">Состав</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="text-align: center;" class="border border-gray-300 p-2">1 23 456 ...</td>
                                        <td style="text-align: center;" class="border border-gray-300 p-2">Мусор от ...</td>
                                        <td style="text-align: center;" class="border border-gray-300 p-2">4</td>
                                        <td style="text-align: center;" class="border border-gray-300 p-2">...</td>
                                        <td style="text-align: center;" class="border border-gray-300 p-2">...</td>
                                        <td style="text-align: center;" class="border border-gray-300 p-2">...</td>
                                    </tr>
                                </tbody>
                            </table>
                        ')),
                    Forms\Components\FileUpload::make('attachment')
                        ->label('Файл Excel')
                        ->required()
                        ->disk('local')
                        ->directory('fkko-imports'),
                ])
                ->action(function (array $data) {
                    $file = Storage::disk('local')->path($data['attachment']);
                    
                    try {
                        $spreadsheet = IOFactory::load($file);
                        $worksheet = $spreadsheet->getActiveSheet();
                        $rows = $worksheet->toArray();
                        
                        $count = 0;
                        foreach ($rows as $index => $row) {
                            if ($index === 0 || empty($row[0])) continue; // Skip header row or empty code

                            $code = str_replace(' ', '', trim($row[0]));
                            $name = $row[1] ?? '';
                            $hazard_class_input = $row[2] ?? null; // Column C
                            $origin = isset($row[3]) ? trim($row[3]) : null; // Column D
                            $aggregate_state = isset($row[4]) ? trim($row[4]) : null; // Column E
                            $chemical_composition = isset($row[5]) ? trim($row[5]) : null; // Column F

                            $hazardClass = 5;
                            if ($hazard_class_input && is_numeric($hazard_class_input)) {
                                $hazardClass = (int) $hazard_class_input;
                            } elseif ($hazard_class_input && preg_match('/I{1,3}|IV|V/', (string) $hazard_class_input, $matches)) {
                                $romanMap = ['I' => 1, 'II' => 2, 'III' => 3, 'IV' => 4, 'V' => 5];
                                $hazardClass = $romanMap[$matches[0]] ?? 5;
                            } else {
                                // По умолчанию берем последнюю цифру кода
                                $lastDigit = substr($code, -1);
                                if (is_numeric($lastDigit)) {
                                    $hazardClass = (int) $lastDigit;
                                }
                            }

                            FkkoCode::updateOrCreate(
                                ['code' => $code],
                                [
                                    'name' => $name,
                                    'hazard_class' => $hazardClass,
                                    'is_active' => true,
                                    'origin' => $origin,
                                    'aggregate_state' => $aggregate_state,
                                    'chemical_composition' => $chemical_composition,
                                ]
                            );
                            $count++;
                        }

                        Storage::disk('local')->delete($data['attachment']);

                        Notification::make()
                            ->title('Импорт завершен')
                            ->success()
                            ->body("Загружено записей: {$count}")
                            ->send();

                    } catch (\Exception $e) {
                         Notification::make()
                            ->title('Ошибка импорта')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\CreateAction::make()->label('Добавить код'),
        ];
    }
}
