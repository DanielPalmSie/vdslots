<?php
require_once 'TestCasinoCashier.php';
class TestInpay extends TestCasinoCashier{

    function __construct(){
        parent::__construct();
        $this->mts->setSupplier('inpay');
        $this->db->truncate('trans_log');
    }

    function withdraw($user, $cents, $insert_pending = true){
        $this->mts->setUser($user);

        if($insert_pending){
            $this->c->insertPendingCommon($user, $cents, [
                'payment_method'      => 'inpay',
                'aut_code'            => $cents,
                'bank_receiver'       => $user->getFullName(),
                'bank_name'           => 'Barclays Australia',
                'bank_account_number' => 999999999,
                'bank_clearnr'        => 1234,
                'iban'                => 'NL89370400440532013000',
                'swift_bic'           => 'NLSNLS'
            ]);
        }

        $p   = $this->getLatestPending($user);

        $res = $this->mts->withdraw($p['id'], $p['user_id'], $p['aut_code'], -1, [
            'currency'              => $p['currency'],
            'bank_receiver'         => $p['bank_receiver'],
            'bank_name'             => $p['bank_name'],
            'bank_branch_code'      => $p['bank_clearnr'],
            'bank_account_number'   => $p['bank_account_number'],
            'bank_country'          => $p['bank_country'],
            'iban'                  => $p['iban'],
            'swift_bic'             => $p['swift_bic'],
            'bank_receiver_address' => $user->getFullAddress(),
        ]);
        
        print_r($res);
    }

    function resetTrs($mts_tr, $u){
        $inpay_id               = uniqid();
        $mts_tr['status']       = 10; 
        $mts_tr['reference_id'] = $inpay_id; 
        $this->mts_db->save('transactions', $mts_tr);
        $p                      = $this->getLatestPending($u);
        $p['mts_id']            = $mts_tr['id'];
        $p['status']            = 'approved';
        $p['ext_id']            = $inpay_id;
        $this->db->sh($u)->save('pending_withdrawals', $p);        
    }
    
    function notification($mts_tr, $u, $status = 'refund_returned'){

        // The basic test here is to simulate an approved situation with a refund to check that the player will get credited etc.
        $params = [
            'order_id'         => $mts_tr['id'],
            'invoice_status'   => $status
        ];

        $to_encrypt               = $params;
        $to_encrypt['secret_key'] = '5Wyk22XH'; 
        ksort($to_encrypt);
        $checksum                 = md5(http_build_query($to_encrypt));

        $params['checksum']       = $checksum;
        
        $url                      = $this->mts_base_url."transfer/inpay/notification";
        $content                  = http_build_query($params);
        $res                      = phive()->post($url, $content, 'application/x-www-form-urlencoded', '', 'mts-inpay-notification');
        
        print_r($res);
    }
}

/*

inpayCustomerId
checksum,
                             Target Country,
                             Target Currency (or source Currency)
                                 Target Amount (or source Amount),
                             transaction Id,
                             
                             Beneficiary's Full Name,
           Beneficiary's Full Name Address, (this field is not required for all countries)
               Beneficiary's Bank Name,
           Beneficiary's Account Number (or IBAN)
               Beneficiary's BIC (this field is not required for all countries)
           
           Originator's FullName
Originator's Address
           Originator's City or Postal code
Originator's Country


        {
            "$schema": "http://json-schema.org/draft-04/schema",
            "description": "A financial service provided by Inpay",
            "type": "object",
            "additionalProperties": false,
            "properties": {
                "inpayCustomerId": {
                    "type": "string",
                    "description": "Your Inpay customer's number. The legacy merchant_id."
                },
                "checksum": {
                    "type": "string",
                    "description": "data signature of the payload"
                },
                "sourceCurrency": {
                    "description": "The 3 letters code that defines the source currency",
                    "type": "string"
                },
                "sourceAmount": {
                    "description": "Defines how much you want to send in source currency",
                    "type": "string",
                    "minLength": 1,
                    "pattern": "\\d+(.\\d{1,2})?"
                },
                "targetCurrency": {
                    "description": "The 3 letters code that defines the target currency",
                    "type": "string",
                    "enum": [
                        "AUD"
                    ]
                },
                "targetAmount": {
                    "description": "Defines the exact amount that beneficiary gets in target currency",
                    "type": "string",
                    "minLength": 1,
                    "pattern": "\\d+(.\\d{1,2})?"
                },
                "targetCountry": {
                    "description": "The 2 letters country code of the target/destination bank account",
                    "type": "string",
                    "enum": [
                        "AU"
                    ]
                },
                "transactionId": {
                    "description": "A unique identifier in your system for this transaction",
                    "type": "string"
                },
                "transactionPurpose": {
                    "description": "Specifies the transaction's type/purpose",
                    "type": "string"
                },
                "remittanceDescription": {
                    "description": "A text that describres the transaction",
                    "type": "string"
                },
                "remittanceReasonCode": {
                    "description": "",
                    "type": "string"
                },
                "beneficiary": {
                    "type": "object",
                    "properties": {
                        "type": {
                            "enum": [
                                "individual",
                                "company"
                            ]
                        },
                        "fullName": {
                            "type": "string",
                            "description": "Beneficiary's full name",
                            "maxLength": 60,
                            "pattern": "^([a-zA-Z]+\\s?)*$"
                        },
                        "fullAddress": {
                            "type": "string",
                            "description": "translation missing: en.properties.beneficiary.address.description"
                        },
                        "bankName": {
                            "type": "string",
                            "description": "Beneficiary's bank name"
                        },
                        "accountNumber": {
                            "type": "string",
                            "description": "Beneficiary bank account number"
                        },
                        "bsbCode": {
                            "type": "string",
                            "minLength": 6,
                            "maxLength": 6,
                            "description": "Branch code of beneficiary account - Also known as domestic bank identifier."
                        }
                    },
                    "required": [
                        "fullName",
                        "fullAddress",
                        "bankName",
                        "accountNumber",
                        "bsbCode"
                    ]
                },
                "originator": {
                    "type": "object",
                    "properties": {
                        "type": {
                            "enum": [
                                "individual",
                                "company"
                            ]
                        },
                        "fullName": {
                            "type": "string",
                            "description": "Originator's full name. Originator is the one that is requesting the disbursement.",
                            "maxLength": 60,
                            "pattern": "^([a-zA-Z]+\\s?)*$"
                        },
                        "address": {
                            "type": "string",
                            "description": "Originator's address (street, number)"
                        },
                        "city": {
                            "type": "string",
                            "description": "Originator's city"
                        },
                        "country": {
                            "type": "string",
                            "description": "Originator's country"
                        },
                        "postalCode": {
                            "type": "string",
                            "description": "Originator's postal code (zip code)"
                        }
                    },
                    "required": [
                        "fullName",
                        "address",
                        "country"
                    ],
                    "oneOf": [
                        {
                            "required": [
                                "city"
                            ]
                        },
          {
              "required": [
                  "postalCode"
              ]
          }
                    ]
                }
            },
            "required": [
                "inpayCustomerId",
                "checksum",
                "targetCountry",
                "targetCurrency",
                "transactionId",
                "beneficiary",
                "originator"
            ],
            "oneOf": [
                {
                    "required": [
                        "targetAmount"
                    ]
                },
      {
          "required": [
              "sourceCurrency",
              "sourceAmount"
]
}
]
}

*/
