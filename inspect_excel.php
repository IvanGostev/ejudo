<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFileType = 'Xls';
$inputFileName = 'public/ЖУДО.xls';

try {
    $reader = IOFactory::createReader($inputFileType);
    $spreadsheet = $reader->load($inputFileName);

    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
        echo "Sheet: " . $worksheet->getTitle() . "\n";

        // Read first 20 rows to get an idea of structure
        foreach ($worksheet->getRowIterator() as $row) {
            if ($row->getRowIndex() > 20)
                break;

            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE);

            $data = [];
            foreach ($cellIterator as $cell) {
                $val = $cell->getValue();
                if (!empty($val)) {
                    $data[] = $cell->getColumn() . $row->getRowIndex() . ": " . $val;
                }
            }
            if (!empty($data)) {
                echo implode(", ", $data) . "\n";
            }
        }
        echo "--------------------------------\n";
    }

} catch (Exception $e) {
    echo 'Error loading file: ', $e->getMessage();
}
