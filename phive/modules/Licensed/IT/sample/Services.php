<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';

//TYPES
require_once __DIR__ . '/../lib/AbstractRequest.php';
require_once __DIR__ . '/../lib/Types/ResidenceType.php';
require_once __DIR__ . '/../lib/Types/LegalEntityType.php';
require_once __DIR__ . '/../lib/Types/DateTimeType.php';
require_once __DIR__ . '/../lib/Types/DateType.php';
require_once __DIR__ . '/../lib/Types/TimeType.php';
require_once __DIR__ . '/../lib/Tables/GamingFamily.php';


//SERVICES
require_once __DIR__ . '/../lib/Services/OpenAccountLegalEntity.php';
require_once __DIR__ . '/../lib/Services/AccountTransactionsEntity.php';
require_once __DIR__ . '/../lib/Services/AccountBonusTransactionsEntity.php';
require_once __DIR__ . '/../lib/Services/AccountStatusUpdateEntity.php';
require_once __DIR__ . '/../lib/Services/AccountBalanceEntity.php';
require_once __DIR__ . '/../lib/Services/SubregistrationEntity.php';



$legal_entity_hq = new ResidenceType();
$legal_entity_hq->setResidentialAddress("Via Piana Timpone, 21");
$legal_entity_hq->setMunicipalityOfResidence("Catanzaro");
$legal_entity_hq->setResidentialPostCode("88100");
$legal_entity_hq->setResidentialProvinceAcronym("CZ");

$legal_entity = new LegalEntityType();
$legal_entity->setCompanyName("Francesco LTF");
$legal_entity->setEmail("passantifrancesco@gmail.com");
$legal_entity->setVatNumber("12345678901");
$legal_entity->setPseudonym("francesco");
$legal_entity->setCompanyHeadquarter($legal_entity_hq);

$open_account = new OpenAccountLegalEntity();
$open_account->setAccountCode("123456789");
$open_account->setTransactionId("123456789");
$open_account->setNetworkId("1234567");
$open_account->setAccountHolder($legal_entity);

$transaction_time = new TimeType('05','30', '00');
$transaction_date = new DateType('21','01','2020');

$transaction_datetime = new DateTimeType();
$transaction_datetime->setDateType($transaction_date);
$transaction_datetime->setTimeType($transaction_time);

$bonus_detail = new BonusDetailType(GamingFamily::$bingo,2,3);

$account_transactions = new AccountTransactionsEntity();
$account_transactions->setAccountCode("123456789");
$account_transactions->setTransactionId("123456789");
$account_transactions->setNetworkId("1234567");
$account_transactions->setAmountBonusBalance("123456");
$account_transactions->setBonusBalanceShareAmount("30");
$account_transactions->setNumberBonusAccountDetails("5");
$account_transactions->setTransactionDateTime($transaction_datetime);
$account_transactions->setBalanceAmount("20");
$account_transactions->setAccountSalesNetworkId("1");
$account_transactions->setAccountNetworkId("2");
$account_transactions->setPaymentMethod("VISA");
$account_transactions->setBonusDetails($bonus_detail);



$bonus_account_transactions = new AccountBonusTransactionsEntity();
$bonus_account_transactions->setAccountCode("123456789");
$bonus_account_transactions->setTransactionId("123456789");
$bonus_account_transactions->setNetworkId("1234567");
$bonus_account_transactions->setBonusBalanceShareAmount("30");
$bonus_account_transactions->setTransactionDateTime($transaction_datetime);
$bonus_account_transactions->setBalanceAmount("20");
$bonus_account_transactions->setAccountSalesNetworkId("1");
$bonus_account_transactions->setAccountNetworkId("2");
$bonus_account_transactions->setPaymentMethod("VISA");
$bonus_account_transactions->setBonusDetails($bonus_detail);
$bonus_account_transactions->setBonusBalanceDetails($bonus_detail);
$bonus_account_transactions->setNumberBonusDetails('11');
$bonus_account_transactions->setNumberBonusBalanceDetails('12');



$change_account_status = new AccountStatusUpdateEntity();
$change_account_status->setAccountCode('123456');
$change_account_status->setAccountNetworkId('123456');
$change_account_status->setAccountStatus('ACTIVE');
$change_account_status->setAccountSalesNetworkId('12');
$change_account_status->setTransactionId("123456789");
$change_account_status->setNetworkId("1234567");
$change_account_status->setChangeReason('ACTIVE');



$account_balance = new AccountBalanceEntity();
$account_balance->setAccountCode("123456789");
$account_balance->setTransactionId("123456789");
$account_balance->setNetworkId("1234567");
$account_balance->setAmountBonusBalance("123456");
$account_balance->setBonusBalanceShareAmount("30");
$account_balance->setNumberBonusAccountDetails("5");
$account_balance->setTransactionDateTime($transaction_datetime);
$account_balance->setBalanceAmount("20");
$account_balance->setAccountSalesNetworkId("1");
$account_balance->setAccountNetworkId("2");
$account_balance->setPaymentMethod("VISA");
$account_balance->setBonusDetails($bonus_detail);



$test = new SubregistrationEntity();

$data = [
            'transaction_id' => '1234',
            'account_code' => '123456',
            'payment_method' => 'payment method',
            'number_bonus_account_details' => '123456789',
            'total_bonus_balance_on_account' => '100.00',
            'balance_amount' => '200.00',
            'transaction_amount' => '10.00',
            'transaction_reason' => 'BET',
            'transaction_datetime' => $transaction_datetime,
            'account_sales_network_id' => 'network id',
            'bonus_details' => $bonus_detail,
            'account_network_id' => 'account network id',
            'network_id' => '123456'
    ];

$test->fill($data);
print_r($test->toArray());
die;


//print_r($test->toArray());

exit();




$pacgClient = new Pacg("test",
    [
        "username" => "Ciao",
        "password" => "Ciao2"
    ],
    "ciao",
    "ciao"
);

//
//try {
//    $response = $pacgClient->openAccountLegalEntity($open_account);
//} catch (Exception $exception) {
//    throw $exception;
//}


//try {
//    $response = $pacgClient->accountTransactionsEntity($account_transactions);
//} catch (Exception $exception) {
//    throw $exception;
//}


//try {
//    $response = $pacgClient->bonusAccountTransactionsEntity($bonus_account_transactions);
//} catch (Exception $exception) {
//    throw $exception;
//}


try {
    $response = $pacgClient->UpdateAccountStatusEntity($change_account_status);
} catch (Exception $exception) {
    throw $exception;
}