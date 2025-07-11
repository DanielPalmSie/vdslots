<?php

namespace Tests\Api\Phive\Modules\Micro\Swintt;

require_once __DIR__ . '/BaseCest.php';

use \ApiTester;
use \Codeception\Example;
use \Codeception\Util\HttpCode;

/**
 * Class WinCest
 * @package Tests\Api\Phive\Modules\Micro\Swintt
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Swintt/WinCest:winAndBet
 */
class WinCest extends BaseCest
{
    /**
     * WinCest constructor.
     *
     * @param null $game_provider_module
     * @param null $sql_module
     * @example new WinCest(phive('Swintt'), phive('SQL'))
     */
    public function __construct($game_provider_module = null, $sql_module = null)
    {
        parent::__construct($game_provider_module, $sql_module);
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataWin
     */
    public function win(ApiTester $I, Example $example)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $balance_after_bet = bcmul(100, $this->getXmlAttribute($this->response, "amt"));

        /**
         * Posts the win.
         */
        $win_parameters = $this->getFundsParameters([
            "amt" => $example['request']['amount'],
            "handid" => $bet_parameters["handid"],
        ]);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the win response.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));
        $I->assertEquals($this->phive_user->data['currency'], $this->getXmlAttribute($this->response, "cur"));

        $expected_balance = $balance_after_bet + bcmul(100, $win_parameters['amt']);
        $I->assertEquals($expected_balance, bcmul(100, $this->getXmlAttribute($this->response, "amt")));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($expected_balance, $db_user['cash_balance']);

        /**
         * Empty wins are not saved to the db. Checks against the database.
         */
        if (floatval($win_parameters['amt'])) {
            $uid = $this->phive_user->data['id'];
            $row = $this->sql_module->sh($uid)->fetchResult("SELECT * FROM wins WHERE user_id = {$uid} AND mg_id = 'swintt_{$win_parameters['txnid']}'");
            $I->assertNotNull($row);
            $I->assertEquals(bcmul(100, $win_parameters['amt'], 2), $row['amount']);
            $I->assertEquals($win_parameters['handid'], $row['trans_id']);
            $I->assertEquals("swintt_{$this->game_id}", $row['game_ref']);
            $I->assertEquals($balance_after_bet, $row['balance']);    // the balance before the win

            $row_round = $this->sql_module->sh($uid)->fetchResult("SELECT * FROM rounds WHERE user_id = {$uid} AND win_id = {$row['id']}");
            $I->assertNotNull($row_round);
            $I->assertEquals("swintt_{$win_parameters['handid']}", $row_round['ext_round_id']);
        }
    }

    /**
     * @param ApiTester $I
     */
    public function winAndBet(ApiTester $I)
    {
        /**
         * Posts the multiple transactions.
         */
        $bet_parameters = $this->getFundsParameters();
        $win_parameters = $this->getFundsParameters([
            "amt" => "2.50",
            "handid" => $bet_parameters["handid"],
        ]);
        $multiple_request = $this->getMultipleFundsRequest([$bet_parameters, $win_parameters]);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $multiple_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the response.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));
        $I->assertEquals($bet_parameters['cur'], $this->getXmlAttribute($this->response, "cur"));

        $balance = bcmul(100, $this->getXmlAttribute($this->response, "amt"));
        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function betInvalidAmount(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $balance_after_bet = bcmul(100, $this->getXmlAttribute($this->response, "amt"));

        /**
         * Posts the win.
         */
        $win_parameters = $this->getFundsParameters([
            "handid" => $bet_parameters["handid"],
        ]);
        $win_parameters['amt'] = 'abc';
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the win response.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_after_bet, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function winInvalidCurrency(ApiTester $I)
    {
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        $win_parameters = $this->getFundsParameters([
            "amt" => "3.50",
            "cur" => "{$this->phive_user->data['currency']}XXX",
            "handid" => $bet_parameters["handid"],
        ]);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);

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
    public function winInvalidUser(ApiTester $I)
    {
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        $win_parameters = $this->getFundsParameters([
            "acctid" => "{$this->user_prefix}{$this->user_identifier}9999",
            "amt" => "3.50",
            "handid" => $bet_parameters["handid"],
        ]);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);

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
    public function winBlockedUser(ApiTester $I, Example $example)
    {
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        $win_parameters = $this->getFundsParameters([
            "amt" => "3.50",
            "handid" => $bet_parameters["handid"],
        ]);
        $win_request = $this->getFundsRequest($win_parameters);
        $balance = $this->phive_user->data['cash_balance'];

        if ($example["status"] == "block") {
            $this->phive_user->block();
        } elseif ($example["status"] == "play_block") {
            $this->phive_user->playBlock();
        } elseif ($example["status"] == "super_block") {
            $this->phive_user->superBlock(false);
        }

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);

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
    public function winInvalidTransaction(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $balance_after_bet = bcmul(100, $this->getXmlAttribute($this->response, "amt"), 2);

        /**
         * Posts the win.
         */
        $win_parameters = $this->getFundsParameters([
            "amt" => "3.50",
            "handid" => $bet_parameters["handid"],
        ]);
        unset($win_parameters['txnid']);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the win.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_after_bet, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function winInvalidRound(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $balance_after_bet = bcmul(100, $this->getXmlAttribute($this->response, "amt"), 2);

        /**
         * Posts the win.
         */
        $win_parameters = $this->getFundsParameters([
            "amt" => "3.50",
        ]);
        unset($win_parameters['handid']);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the win.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_after_bet, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function winIdempotentTransaction(ApiTester $I)
    {
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        $win_parameters = $this->getFundsParameters([
            "amt" => "3.50",
            "handid" => $bet_parameters["handid"],
        ]);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $balance = 100 * $this->getXmlAttribute($this->response, "amt");

        /**
         * Sends an idempotent transaction
         */
        $win2_parameters = $this->getFundsParameters([
            "amt" => $win_parameters["amt"],
            "cur" => $win_parameters["cur"],
            "txnid" => $win_parameters["txnid"],
            "handid" => $win_parameters["handid"],
        ]);
        $win2_request = $this->getFundsRequest($win2_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win2_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $new_balance = bcmul(100, $this->getXmlAttribute($this->response, "amt"), 2);
        $I->assertEquals($balance, $new_balance);
    }

    /**
     * @param ApiTester $I
     */
    public function winDuplicateTransaction(ApiTester $I)
    {
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        $win_parameters = $this->getFundsParameters([
            "amt" => "3.50",
            "handid" => $bet_parameters["handid"],
        ]);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $balance = 100 * $this->getXmlAttribute($this->response, "amt");

        /**
         * Sends an idempotent transaction
         */
        $win2_parameters = $this->getFundsParameters([
            "amt" => bcadd(5, $win_parameters["amt"], 2),
            "cur" => $win_parameters["cur"],
            "txnid" => $win_parameters["txnid"],
            "handid" => $win_parameters["handid"],
        ]);
        $win2_request = $this->getFundsRequest($win2_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win2_request);
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
    public function winWithoutBet(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $balance_after_bet = bcmul(100, $this->getXmlAttribute($this->response, "amt"), 2);

        /**
         * Posts the win.
         */
        $win_parameters = $this->getFundsParameters([
            "amt" => "3.50",
            "handid" => 1,
        ]);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the win.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));

        /**
         * Validates the balance remain unchanged.
         */
        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_after_bet, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function winDuplicateRound(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Posts the win.
         */
        $win_parameters = $this->getFundsParameters([
            "amt" => "3.50",
            "handid" => $bet_parameters['handid'],
        ]);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the win.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $balance_after_win = bcmul(100, $this->getXmlAttribute($this->response, "amt"));

        /**
         * Posts another win for the same round.
         */
        $win_parameters = $this->getFundsParameters([
            "amt" => "3.50",
            "handid" => $bet_parameters['handid'],
        ]);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the win.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));

        /**
         * Validates the balance remain unchanged.
         */
        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_after_win, $db_user['cash_balance']);
    }

    /**
     * Data provider.
     *
     * @return array
     */
    protected function dataWin(): array
    {
        return [
            [
                "_label" => "win_1255",
                "request" => [
                    "amount" => "12.55",
                ],
            ],

            [
                "_label" => "win_1",
                "request" => [
                    "amount" => "0.01",
                ],
            ],

            [
                "_label" => "bet_9",
                "request" => [
                    "amount" => "0.09",
                ],
            ],

            [
                "_label" => "bet_0",
                "request" => [
                    "amount" => "0.00",
                ],
            ],
        ];
    }
}
