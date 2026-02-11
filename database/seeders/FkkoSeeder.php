<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\FkkoCode;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FkkoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = public_path('list.xlsx');

        if (!file_exists($filePath)) {
            $this->command->error("File not found: {$filePath}");
            return;
        }

        $this->command->info("Loading file: {$filePath}");

        try {
            $spreadsheet = IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
        } catch (\Exception $e) {
            $this->command->error("Error loading file: " . $e->getMessage());
            return;
        }

        $count = 0;
        foreach ($rows as $index => $row) {

            if (empty($row[0])) {
                continue;
            }

            $code = trim((string) $row[0]);
            $name = trim((string) ($row[1] ?? ''));

            if (empty($code)) {
                continue;
            }

            $cleanCode = str_replace([' ', '-'], '', $code);
            $hazardClass = (int) substr($cleanCode, -1);

            FkkoCode::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'hazard_class' => $hazardClass,
                    'is_active' => true,
                ]
            );

            $count++;

            if ($count % 100 === 0) {
                $this->command->info("Processed $count rows...");
            }
        }

        $this->command->info("Successfully processed {$count} FKKO records.");
    }
}
