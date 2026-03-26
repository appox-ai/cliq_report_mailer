<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

function getRecords($startDate, $endDate) {
    $spreadSheetContent = [];

    // Get checkin and checkout records from csv
    $lines = [];
    $files = glob('input/*.csv');
    foreach ($files as $file) {
        $lines = array_merge($lines, file($file));
    }
    foreach ($lines as $line) {
        $records[] = str_getcsv($line);
    }

    // Get users
    $users = json_decode(file_get_contents('users.json'), true);

    // Get all agreements
    $agreements = json_decode(file_get_contents('agreements.json'), true);

    foreach ($agreements as $agreement) {
        // Process only current agreements
        if( $startDate <= $agreement['endDate'] && $endDate >= $agreement['startDate']){

            foreach ($users as $user) {
                if(!isset($user['agreements'])) {
                    continue;
                }

                foreach ($user['agreements'] as $user_contract) {
                    // User belongs to this agreement
                    if($user_contract['contract'] === $agreement['contract']) {

                        $userRecords = array_filter($records, function($row) use ($user) {
                            return $row[1] === $user['name'];
                        });

                        $hours = 0;
                        $days = 0;
                        $previousRecordDate = null;

                        foreach ($userRecords as $row) {

                            // skip header row from csv data
                            if($row[0] === 'UID') {
                                continue;
                            }
                            
                            if($row[2] >= $startDate && $row[2] <= $endDate && $row[1] === $user['name']) {
                                if($previousRecordDate === $row[2]) continue;

                                if( date('l', strtotime($row[2])) != "Saturday" && date('l', strtotime($row[2])) != "Sunday") {
                                    $hours += $user_contract['hours_per_day'];
                                    $days++;
                                }
                            }
                            $previousRecordDate = $row[2];
                        }
                       
                        $spreadSheetContent[] = [
                            'User' => $user['name'],
                            'Contract' => $user_contract['contract'],
                            'hours_per_day' => $user_contract['hours_per_day'],
                            'days' => (string)$days,
                            'Hours' => (string)$hours
                        ];

                    }
                }
 
            }
        }
    }
    return $spreadSheetContent;
}

function createSpreadsheet($spreadSheetContent, $startDate, $endDate) {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $month = date('m', strtotime($startDate));
    $year = date('Y', strtotime($startDate));

    $period = getMonthName($month) . ' ' . $year;

    // Add content to sheet
    $sheet->fromArray(
        ['Nom','Client-code-contrat', $period],
        NULL,
        'A1'
    );
    
    $sheet->fromArray(
        ['Heures per jour', 'Jours', 'Heures totales'],
        NULL,
        'C2'
    );
    $sheet->fromArray($spreadSheetContent, NULL, 'A3');
    
    // Styling sheet
    $sheet->mergeCells('C1:E1');
    $sheet->mergeCells('A1:A2');
    $sheet->mergeCells('B1:B2');

    $sheet->getStyle('A1:E2')
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN)
        ->getColor()->setARGB('FFFFFFFF');

    $sheet->getStyle('A3:E' . (count($spreadSheetContent) + 2))
        ->getBorders()
        ->getAllBorders()
        ->setBorderStyle(Border::BORDER_THIN)
        ->getColor()->setARGB('3c3c3c');

    $sheet->getStyle('A1:E2')
        ->getFill()
        ->setFillType(Fill::FILL_SOLID)
        ->getStartColor()->setARGB('3c3c3c');

    $sheet->getStyle('A1:E2')->applyFromArray([
        'font' => [
            'color' => ['argb' => 'FFFFFFFF'],
            'bold' => false,
            'size' => 12,
        ],
    ]);

    $sheet->getStyle('A1:E2')
        ->getAlignment()
        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->getColumnDimension('A')->setWidth(20);
    $sheet->getColumnDimension('B')->setWidth(25);
    $sheet->getColumnDimension('C')->setWidth(20);
    $sheet->getColumnDimension('D')->setWidth(20);
    $sheet->getColumnDimension('E')->setWidth(20);

    $writer = new Xlsx($spreadsheet);

    $outputDir = 'monthly_output';
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0755, true);
    }

    $filename = $year . '_' . $month . '_reporte_mensual.xlsx';
    $writer->save($outputDir . '/' . $filename);

    return $outputDir . '/' . $filename;
}

function getMonthName($month) {
    $months = [
        '01' => 'Janvier',
        '02' => 'Février',
        '03' => 'Mars',
        '04' => 'Avril',
        '05' => 'Mai',
        '06' => 'Juin',
        '07' => 'Juillet',
        '08' => 'Août',
        '09' => 'Septembre',
        '10' => 'Octobre',
        '11' => 'Novembre',
        '12' => 'Décembre'
    ];
    return $months[$month];
}

function main() {
    $firstDayOfMonth = date('Y-m-d', strtotime('first day of previous month'));
    $lastDayOfMonth = date('Y-m-d', strtotime('last day of previous month'));
    
    // compute records
    $spreadsheetContent = getRecords($firstDayOfMonth, $lastDayOfMonth);

    // create spreadsheet
    $filename = createSpreadsheet($spreadsheetContent, $firstDayOfMonth, $lastDayOfMonth);

    return true;
}

main();