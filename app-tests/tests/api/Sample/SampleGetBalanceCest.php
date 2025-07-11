<?php

namespace Tests\Api\Sample;

use \ApiTester;

class SampleGetBalanceCest
{
    /**
     * @param ApiTester $I
     */
    public function getBalance(ApiTester $I)
    {
        $user_id = 5541343;     // devtestmt
        $api_url = "playtech.php/getbalance";      // The URL base is defined in api.suite.yml

        $row = phive('SQL')->sh($user_id)->fetchResult("SELECT * FROM users WHERE id = {$user_id}");
        $I->assertNotNull($row);
        $balance = $row['cash_balance'];

        $request = [
            'username' => $user_id,
            'externalToken' => str_replace('-', '', phive()->uuid()),
            'requestId' => uniqid(),
        ];

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($api_url, json_encode($request));
        $I->assertNotNull($I->grabResponse());
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "balance" => [
                "real" =>  $balance / 100,
            ],
        ]);
    }
}