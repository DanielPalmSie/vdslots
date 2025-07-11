<?php
require_once __DIR__ . '../../../../../phive.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;

function fmtn($integerValue){
    return number_format($integerValue / 100, 2, '.', '');
}

function fillSheet(&$sheet, $data){
    foreach ($data as $rowIndex => $rowData) {
        foreach ($rowData as $columnIndex => $cellData) {
            $cell = $sheet->getCellByColumnAndRow($columnIndex + 1, $rowIndex + 1);
            $cell->setValue($cellData);

            if ($rowIndex === 0) { // Apply bold font to the first row (titles)
                $boldFontStyle = (new Font())->setBold(true);
                $cell->getStyle()->setFont($boldFontStyle);
            }
        }
    }
}


switch ($_REQUEST['action']) {
    case 'downloadaccounthistory':
        $user = cu();

        if(empty($user)){
            exit;
        }
        $user_id = cu()->userId;

        $start_date_dt = new DateTime();
        $start_date_dt->sub(new DateInterval('P1Y')); // Subtract 1 year
        $start_date_dt->add(new DateInterval('P1D')); // Add 1 day

        $start_date = $start_date_dt->format('Y-m-d');
        $start_date_str = $start_date.' 00:00';

        $end_date = date('Y-m-d');
        $end_date_str = date('Y-m-d H:i');

        $nextDay = strtotime('+1 day', strtotime($end_date));
        $nextDayFormatted = date('Y-m-d', $nextDay);

        //opening balance on a start date (year ago)
        $opening_balance = phive('Cashier')->getBalanceSumByDate($user_id, $start_date) ?? 0;
        //balance at this moment
        $closing_balance = cu()->getBalance() ?? 0;

        //deposits and withdrawals
        $data = phive('Cashier')->getTransactionSumsByUserIdProvider($user_id, $start_date, $nextDayFormatted);
        $sum_deposits    = $data['sum_deposits'] ?? 0;
        $sum_withdrawals = $data['sum_withdrawals'] ?? 0;

        //Charges: wager and other fees
        $sum_wagers = phive('Cashier')->getFeeSumByPeriod($user_id, 'wager', $start_date, $nextDayFormatted) ?? 0;
        $sum_fees = phive('Cashier')->getFeeSumByPeriod($user_id, 'fee', $start_date, $nextDayFormatted) ?? 0;
        $total_charges = $sum_wagers+$sum_fees;

        $bonuses = phive('Bonuses')->getUserBonusesForPeriod($user_id, $start_date, $nextDayFormatted);

        $headerInfo = cu()->getFullName().'. ID: '.cu()->getId();

        //formatted data to place in an Excel
        $data = array(
            array(t('game-history.company-info'), '', ''),
            array($headerInfo, '', ''),
            array('', t('game-history.timestamp'), t('game-history.amount'),),
            array(t('game-history.requested-date'), $end_date_str, 'N/A'),
            array(t('game-history.closing-balance'), $end_date_str, fmtn($closing_balance)),
            array(t('game-history.opening-balance'), $start_date_str, fmtn($opening_balance)),
            array(t('game-history.sum-of-withdrawals'), "$start_date_str-$end_date_str", fmtn($sum_withdrawals)),
            array(t('game-history.sum-of-deposits'), "$start_date_str-$end_date_str", fmtn($sum_deposits)),
            array(t('game-history.sum-of-charges'), "$start_date_str-$end_date_str", fmtn($sum_wagers)),
            array(t('game-history.sum-of-charges-other'), "$start_date_str-$end_date_str", fmtn($sum_fees)),
            array(t('game-history.sum-of-charges-total'), "$start_date_str-$end_date_str", fmtn($total_charges)),
        );

        $spreadsheet = new Spreadsheet();

        $sheet1 = $spreadsheet->getActiveSheet();

        fillSheet($sheet1, $data);

        $sheet1->setTitle(t('game-history.overview'));
        $sheet1->getColumnDimension('A')->setWidth(50); // Set width of column A
        $sheet1->getColumnDimension('B')->setWidth(50); // Set width of column B
        $sheet1->getColumnDimension('C')->setWidth(20); // Set width of column C

        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle(t('game-history.prizes'));

        $data2 = array(
            array(t('game-history.description'), t('game-history.amount'), t('game-history.date'), t('game-history.status'))
        );

        foreach ($bonuses as $bonus) {
            $bonus_name = pt( empty($bonus['bonus']) ? phive('Bonuses')->nameById($bonus['bonus_id']) : $bonus['bonus'] );
            $bonus_status = t("bonus.status.{$bonus['bonus_status']}");
            $bonus_data = [$bonus_name, fmtn($bonus['bonus_amount']), $bonus['activation_time'], $bonus_status];
            $data2[] = $bonus_data;
        }

        fillSheet($sheet2, $data2);
        $sheet2->getColumnDimension('A')->setWidth(50); // Set width of column A
        $sheet2->getColumnDimension('B')->setWidth(20); // Set width of column B
        $sheet2->getColumnDimension('C')->setWidth(30); // Set width of column C
        $sheet2->getColumnDimension('D')->setWidth(30); // Set width of column D


        $writer = new Xlsx($spreadsheet);

        // Set headers for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=accounthistory-$end_date_str.xlsx");
        header('Cache-Control: max-age=0');

        $writer->save('php://output');

        break;
    default:
}
