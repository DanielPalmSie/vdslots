<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';
//TYPES
require_once __DIR__ . '/../lib/AbstractRequest.php';
require_once __DIR__ . '/../lib/Types/DateTimeType.php';
require_once __DIR__ . '/../lib/Types/DateType.php';
require_once __DIR__ . '/../lib/Types/TimeType.php';
require_once __DIR__ . '/../lib/Types/BonusDetailType.php';
require_once __DIR__ . '/../lib/Tables/GamingFamily.php';

//SERVICES
require_once __DIR__ . '/../lib/Services/AccountBalanceEntity_Example.php';


$transaction_time = new TimeType('05','30', '00');
$transaction_date = new DateType('21','01','2020');

$transaction_datetime = new DateTimeType();
$transaction_datetime->setDateType($transaction_date);
$transaction_datetime->setTimeType($transaction_time);

$bonus_detail = new BonusDetailType(GamingFamily::$bingo,2,3);

$test = new AccountBalanceEntityExample();

$data = [
            'transaction_id'             => '1234',
            'account_code'               => '123456',
            'amount_bonus_balance'       => '123456',
            'total_bonus_balance_on_account' => '30',
            'balance_amount'             => '200.00',
            'transaction_amount'         => '10.00',
            'transaction_reason'         => 'BET',
            'transaction_datetime'       => $transaction_datetime,
            'account_sales_network_id'   => 'network id',
            'bonus_details'              => $bonus_detail,
            'account_network_id'         => 'account network id',
            'network_id'                 => '123456'
    ];


$test->fill($data);

print_r('$test object: ');
print_r($test);

print_r('$test array: ');
print_r($test->toArray());

exit();
