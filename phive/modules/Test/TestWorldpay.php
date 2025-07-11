<?php
require_once 'TestCasinoCashier.php';
class TestWorldpay extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->db->truncate('trans_log');
    }


    function testApplePayDeposit($u, $cents){
        $extra = ['token' => ['paymentData' => array (
            'version' => 'EC_v1',
            'data' => '+Dr4J9/2mutuLXzDx4aOOAixDPs4grTbczqJzfVpONmYastGKtmCE9NrHw/QkkJ+q330geEcnTwkloUfcMSMo7HV3z5ICDvRATUnnT42sIhp9VXYi4fQqmugZZnyseZQjyt4x82ZsoYutgvw6yo3fJg3xf7eAHUNt7KychtMBMrJTK4z/qHYYrux1lnZABPNHrYDG3FCrvweFgo1xT9WuYiVCN3NiYOKpbBJsJxI6FhFJ/7ecm6MsPC5jXVBlabFDSySnpO25SnoWfCZ7kk21RdxkgFCs6aL58zCXuQhzedmLU5fQKSGFgiXBVxS/lD8naJe0LZ/eb5dvEIpEGSeORvVS4Fxv55U9Yreel27KsDC18xUXHjD+nHeGOMEcSxIGgdViCVkCu8kRQpJqQ==',
            'signature' => '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',
            'header' => 
            array (
                'ephemeralPublicKey' => 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAERMmOmDC1T8z6K2mFFWto2GT2iLCse69Ub0xs4/Z6vndzQMDqJvvcdcp0L9Q0hztmgclRKOEiZXR4gsrLhWCKZg==',
                'publicKeyHash' => 'rEgtszzAel6/1VeRQ0SeBX2j3RtiFgVQJDDyiqHEr+4=',
                'transactionId' => '6c19bc109caa18f841d63ef61a3f8d01a76dc0acfb706f110d6a0c1b06794cf6',
            ))]];

        $mts = new Mts('worldpay', $u);
        $mts->setSubSupplier('applepay');
        $res = $mts->deposit($u, $cents, $extra);            
        print_r($res);
    }
    
    function testBankWithdrawal($u, $cents){
        $insert = [
            'iban'           => 'GB36444488881234567890',
            'payment_method' => 'worldpay',
            'aut_code'       => $cents
        ];

        $pid = $this->c->insertPendingCommon($u, $cents, $insert);

        $res = $this->c->payPending($pid, $cents);
        print_r([$pid, $res]);
    }
    
    function mtsBankNotification($mts_tr, $u){
        ob_start();
        echo '<?xml version="1.0" encoding="UTF-8"?>';
    ?>
        <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <soap:Body>
                <PaymentOutReversalNotification xmlns="http://apilistener.envoyservices.com">
                    <reversalInfo>
                        <originalPaymentInfo>
                            <merchantPaymentReference><?php echo $mts_tr['id'] ?></merchantPaymentReference>
                        </originalPaymentInfo>
                    </reversalInfo>
                </PaymentOutReversalNotification>
            </soap:Body>
        </soap:Envelope>
    <?php

        $xml = ob_get_clean();

        $url = $this->mts_base_url."user/transfer/deposit/confirm?supplier=worldpaybank";

        return phive()->post($url, $xml, 'text/xml', '', 'mts-wpbank-notification');
    }

    function setupApplePayForWithdraw($u, $clear_tables = false){
        // Apple pay withdraw setup -- start
        $this->approveKyc($u);

        if($clear_tables){
            $this->clearTable($u, 'pending_withdrawals');
            $this->clearTable($u, 'deposits');
        }

        $this->testApplePayDeposit($u, 25000);

        $card = ['card_num' =>  '999999999999999', 'exp_year' => '20', 'exp_month' => '10', 'cvv' => '737', 'three_d' => 'Y'];
        $mts_tr = $this->getMtsTr($u);
        $mts_db = phive('SQL')->doDb('mts');
        $mts_tr['status'] = 0;
        //$mts_tr['card_id'] = 1;
        $mts_db->save('transactions', $mts_tr);
        $res1 = $this->mtsWpNotification($mts_tr, $u, 'AUTHORISED', true, $card);

        $this->refreshMtsTr($u, $mts_tr);
        
        $this->saveMtsRecurring($mts_tr);
        print_r(['auth' => $res1]);
        phive('SQL')->doDb('dmapi')->query("UPDATE documents SET status = 2 WHERE user_id = ".$u->getId());
        $this->approveKyc($u);
        // Apple pay withdraw -- setup end
    }
    
    function mtsWpNotification($mts_tr, $u, $ev_code = 'AUTHORISED', $cc = false, $extra = []){

        $card_number = phive('WireCard')->getSixFourAsterisk($extra['card_num']);

        ob_start();
        echo '<?xml version="1.0" encoding="UTF-8"?>';
    ?>

        <!DOCTYPE paymentService PUBLIC "-//Worldpay//DTD Worldpay PaymentService v1//EN" "http://dtd.worldpay.com/paymentService_v1.dtd">
        <paymentService version="1.4" merchantCode="Your_merchant_code"> 
            <notify>
                <orderStatusEvent orderCode="<?php echo $mts_tr['id'] ?>"> 
                    <payment>
                        <paymentMethod>VISA_CREDIT-SSL</paymentMethod>
                        <paymentMethodDetail>
                            <card number="<?php echo $card_number ?>" type="creditcard">
                                <expiryDate>
                                    <date month="<?php echo $extra['exp_month'] ?>" year="<?php echo $extra['exp_year'] ?>"/>
                                </expiryDate>
                            </card>
                        </paymentMethodDetail>
                        <amount value="<?php echo $mts_tr['amount'] ?>" currencyCode="<?php echo $u->getCurrency() ?>" exponent="2" debitCreditIndicator="credit"/>
                        <lastEvent>AUTHORISED</lastEvent>       
                        <AuthorisationId id="622206"/>
                        <CVCResultCode description="C"/>
                        <AVSResultCode description="E"/>
                        <AAVAddressResultCode description="B"/>
                        <AAVPostcodeResultCode description="B"/>
                        <AAVCardholderNameResultCode description="B"/>
                        <AAVTelephoneResultCode description="B"/>
                        <AAVEmailResultCode description="B"/>
                        <ThreeDSecureResult description="Cardholder authenticated">
                            <eci>05</eci>
                            <cavv>MAAAAAAAAAAAAAAAAAAAAAAAAAA</cavv>
                        </ThreeDSecureResult>
                        <cardHolderName>***</cardHolderName>
                        <issuerCountryCode><?php echo $u->getCountry() ?></issuerCountryCode>
                        <cardNumber><?php echo $card_number ?></cardNumber>
                        <riskScore value="0"/>
                        <schemeResponse>
		            <transactionIdentifier><?php echo uniqid() ?></transactionIdentifier>
	                </schemeResponse>
                    </payment>
                    <journal journalType="<?php echo $ev_code ?>" sent="n">
                        <bookingDate>
                            <date dayOfMonth="01" month="01" year="2020"/> 
                        </bookingDate>
                        <accountTx accountType="IN_PROCESS_AUTHORISED" batchId="30">
                            <amount debitCreditIndicator="credit" exponent="2" currencyCode="<?php echo $u->getCurrency() ?>" value="<?php echo $mts_tr['amount'] ?>"/>
                        </accountTx>
                    </journal>
                </orderStatusEvent>
            </notify>
        </paymentService>
        <?php 
        
        $body = ob_get_clean();
        print_r(['Posting this body:', $body]);
        $url  = $this->mts_base_url."user/transfer/deposit/confirm?supplier=worldpay";
        // $res1 = phive()->post($url, $body, 'text/xml', '', 'mts-wp-notification');
        $res2 = phive()->post($url, $body, 'text/xml', '', 'mts-wp-notification');
        print_r(["WP notification results", $res1, $res2]);
        return $res1;
    }
        
}
