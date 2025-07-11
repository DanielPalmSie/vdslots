<?php

namespace Tests\Api\Phive\Modules\Micro\Swintt;

require_once __DIR__ . '/BaseCest.php';

use \ApiTester;
use \Codeception\Example;
use \Codeception\Util\HttpCode;

/**
 * Class BetCest
 * @package Tests\Api\Phive\Modules\Micro\Swintt
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Swintt/BetCest:betInvalidAmount
 */
class BetCest extends BaseCest
{
    /**
     * BetCest constructor.
     *
     * @param null $game_provider_module
     * @param null $sql_module
     * @example new BetCest(phive('Swintt'), phive('SQL'))
     */
    public function __construct($game_provider_module = null, $sql_module = null)
    {
        parent::__construct($game_provider_module, $sql_module);
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataBet
     */
    public function betValid(ApiTester $I, Example $example)
    {
        $parameters = $this->getFundsParameters([
            "amt" => $example['request']['amount'],
        ]);
        $request = $this->getFundsRequest($parameters);

        $balance = $this->phive_user->data['cash_balance'];

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $I->assertEquals($this->phive_user->data['currency'], $this->getXmlAttribute($this->response, "cur"));
        $expected_balance = $balance - (100 * abs($parameters['amt']));
        $new_balance = round(100 * $this->getXmlAttribute($this->response, "amt"));
        $I->assertEquals($expected_balance, $new_balance);

        /**
         * Checks against the database.
         */
        $uid = $this->phive_user->data['id'];
        $row = $this->sql_module->sh($uid)->fetchResult("SELECT * FROM bets WHERE user_id = {$uid} AND mg_id = 'swintt_{$parameters['txnid']}'");
        $I->assertNotNull($row);
        $I->assertEquals(-(int)bcmul(100, $parameters['amt'], 2), $row['amount']);
        $I->assertEquals($parameters['handid'], $row['trans_id']);
        $I->assertEquals("swintt_{$this->game_id}", $row['game_ref']);
        $I->assertEquals($balance, $row['balance']);    // the balance before the transaction

        $row_round = $this->sql_module->sh($uid)->fetchResult("SELECT * FROM rounds WHERE user_id = {$uid} AND bet_id = {$row['id']}");
        $I->assertNotNull($row_round);
        $I->assertEquals("swintt_{$parameters['handid']}", $row_round['ext_round_id']);
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @example { "amount": "abc" }
     * @example { "amount": "0" }
     */
    public function betInvalidAmount(ApiTester $I, Example $example)
    {
        $parameters = $this->getFundsParameters();
        $parameters['amt'] = 'abc';
        $request = $this->getFundsRequest($parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));

        $uid = $this->phive_user->data['id'];
        $row = $this->sql_module->sh($uid)->fetchResult("SELECT * FROM bets WHERE user_id = {$uid} AND mg_id = 'swintt_{$parameters['txnid']}'");
        $I->assertNull($row);
    }

    /**
     * @param ApiTester $I
     */
    public function betInvalidCurrency(ApiTester $I)
    {
        $request = $this->getFundsRequest($this->getFundsParameters([
            "cur" => "{$this->phive_user->data['currency']}XXX",
        ]));

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("1001", $this->getXmlAttribute($this->response, "err"));
    }

    /**
     * @param ApiTester $I
     */
    public function betInvalidUser(ApiTester $I)
    {
        $request = $this->getFundsRequest($this->getFundsParameters([
            "acctid" => "{$this->user_identifier}9999",
        ]));

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("1000", $this->getXmlAttribute($this->response, "err"));
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @example { "status": "block" }
     * @example { "status": "play_block" }
     * @example { "status": "super_block" }
     */
    public function betBlockedUser(ApiTester $I, Example $example)
    {
        $request = $this->getFundsRequest($this->getFundsParameters());
        $balance = $this->phive_user->data['cash_balance'];

        if ($example["status"] == "block") {
            $this->phive_user->block();
        } elseif ($example["status"] == "play_block") {
            $this->phive_user->playBlock();
        } elseif ($example["status"] == "super_block") {
            $this->phive_user->superBlock(false);
        }

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $this->unblockPhiveUser();

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("1004", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function betInsufficientFunds(ApiTester $I)
    {
        $this->phive_user = cu($this->user_identifier);
        $balance = $this->phive_user->data['cash_balance'];
        $amount = number_format($balance + 1000, 2, '.', '');

        $request = $this->getFundsRequest($this->getFundsParameters([
            "amt" => "-{$amount}",
        ]));

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("1002", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function betInvalidTransaction(ApiTester $I)
    {
        $parameters = $this->getFundsParameters();
        unset($parameters['txnid']);
        $request = $this->getFundsRequest($parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));
    }

    /**
     * @param ApiTester $I
     */
    public function betInvalidRound(ApiTester $I)
    {
        $parameters = $this->getFundsParameters();
        unset($parameters['handid']);
        $request = $this->getFundsRequest($parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));
    }

    /**
     * @param ApiTester $I
     */
    public function betIdempotentTransaction(ApiTester $I)
    {
        $request = $this->getFundsRequest($this->getFundsParameters());

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $balance = 100 * $this->getXmlAttribute($this->response, "amt");

        /**
         * Sends an idempotent bet
         */
        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function betDuplicateTransaction(ApiTester $I)
    {
        $parameters = $this->getFundsParameters();
        $request = $this->getFundsRequest($parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $balance = 100 * $this->getXmlAttribute($this->response, "amt");

        /**
         * Sends a duplicate transaction
         */
        $parameters2 = $this->getFundsParameters([
            'txnid' => $parameters['txnid'],
            'amt' => bcsub($parameters['amt'], "2.50", 2),
        ]);
        $request = $this->getFundsRequest($parameters2);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function betDuplicateRound(ApiTester $I)
    {
        $parameters = $this->getFundsParameters();
        $request = $this->getFundsRequest($parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $balance = 100 * $this->getXmlAttribute($this->response, "amt");

        /**
         * Sends a duplicate round ID.
         */
        $parameters2 = $this->getFundsParameters([
            'handid' => $parameters['handid'],
            'amt' => bcsub($parameters['amt'], "2.50", 2),
        ]);
        $request = $this->getFundsRequest($parameters2);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance, $db_user['cash_balance']);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    protected function dataBet(): array
    {
        return [
            [
                "_label" => "bet_1",
                "request" => [
                    "amount" => "-1.00",
                ],
                "response" => [
                    "error_code" => "0",
                ],
            ],

            [
                "_label" => "bet_1_cent",
                "request" => [
                    "amount" => "-0.01",
                ],
                "response" => [
                    "error_code" => "0",
                ],
            ],

            [
                "_label" => "bet_9_cents",
                "request" => [
                    "amount" => "-0.09",
                ],
                "response" => [
                    "error_code" => "0",
                ],
            ],

            [
                "_label" => "bet_12",
                "request" => [
                    "amount" => "-12.50",
                ],
                "response" => [
                    "error_code" => "0",
                ],
            ],
        ];
    }
}
