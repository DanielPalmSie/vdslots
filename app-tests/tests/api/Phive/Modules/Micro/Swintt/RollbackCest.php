<?php

namespace Tests\Api\Phive\Modules\Micro\Swintt;

require_once __DIR__ . '/BaseCest.php';

use \ApiTester;
use \Codeception\Example;
use \Codeception\Util\HttpCode;

/**
 * Class RollbackCest
 * @package Tests\Api\Phive\Modules\Micro\Swintt
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Swintt/RollbackCest
 */
class RollbackCest extends BaseCest
{
    /**
     * RollbackCest constructor.
     *
     * @param null $game_provider_module
     * @param null $sql_module
     * @example new RollbackCest(phive('Swintt'), phive('SQL'))
     */
    public function __construct($game_provider_module = null, $sql_module = null)
    {
        parent::__construct($game_provider_module, $sql_module);
    }

    /**
     * @param ApiTester $I
     */
    public function rollback(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $balance_before_bet = $this->phive_user->data['cash_balance'];

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        /**
         * Posts the rollback.
         */
        $rollback_parameters = $this->getFundsParameters([
            "canceltxnid" => $bet_parameters['txnid'],
            "amt" => abs($bet_parameters['amt']),
        ]);
        $rollback_request = $this->getFundsRequest($rollback_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $rollback_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the rollback.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $balance_after_rollback = bcmul(100, $this->getXmlAttribute($this->response, "amt"));
        $I->assertEquals($balance_before_bet, $balance_after_rollback);

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_before_bet, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function partialRollback(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters([
            "amt" => "-4.00",
        ]);
        $bet_request = $this->getFundsRequest($bet_parameters);

        $balance = $this->phive_user->data['cash_balance'];

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Posts the rollback.
         */
        $rollback_parameters = $this->getFundsParameters([
            "canceltxnid" => $bet_parameters['txnid'],
            "amt" => "1.50",
        ]);
        $rollback_request = $this->getFundsRequest($rollback_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $rollback_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the rollback.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $expected_balance = bcmul(100, bcadd($bet_parameters['amt'], $rollback_parameters['amt'], 2)) + $balance;
        $balance_after_rollback = bcmul(100, $this->getXmlAttribute($this->response, "amt"));
        $I->assertEquals($expected_balance, $balance_after_rollback);

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($expected_balance, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function rollbackInvalidUser(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $balance_after_bet = $db_user['cash_balance'];

        /**
         * Posts the rollback.
         */
        $rollback_parameters = $this->getFundsParameters([
            "acctid" => "{$this->user_prefix}{$this->user_identifier}9999",
            "canceltxnid" => $bet_parameters['txnid'],
            "amt" => abs($bet_parameters['amt']),
        ]);
        $rollback_request = $this->getFundsRequest($rollback_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $rollback_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the rollback.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("1000", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_after_bet, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function rollbackInvalidTransaction(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $balance_after_bet = $db_user['cash_balance'];

        /**
         * Posts the rollback.
         */
        $rollback_parameters = $this->getFundsParameters([
            "canceltxnid" => $bet_parameters['txnid'] . "99999",
            "amt" => abs($bet_parameters['amt']),
        ]);
        $rollback_request = $this->getFundsRequest($rollback_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $rollback_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the rollback.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("1000", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_after_bet, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function ignoreIdempotentRollback(ApiTester $I)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        /**
         * Posts the rollback.
         */
        $rollback_parameters = $this->getFundsParameters([
            "canceltxnid" => $bet_parameters['txnid'],
            "amt" => abs($bet_parameters['amt']),
        ]);
        $rollback_request = $this->getFundsRequest($rollback_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $rollback_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $balance_after_rollback = $db_user['cash_balance'];

        /**
         * Sends an idempotent rollback
         */
        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $rollback_request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the idempotent rollback.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $balance_after_idempotent_rollback = bcmul(100, $this->getXmlAttribute($this->response, "amt"));
        $I->assertEquals($balance_after_rollback, $balance_after_idempotent_rollback);
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @example { "status": "block" }
     * @ example { "status": "play_block" }
     * @ example { "status": "super_block" }
     */
    public function blockedPlayerCanRollback(ApiTester $I, Example $example)
    {
        /**
         * Posts the bet.
         */
        $bet_parameters = $this->getFundsParameters();
        $bet_request = $this->getFundsRequest($bet_parameters);

        $balance_before_bet = $this->phive_user->data['cash_balance'];

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $bet_request);
        $I->seeResponseCodeIs(HttpCode::OK);

        /**
         * Blocks the user
         */
        if ($example["status"] == "block") {
            $this->phive_user->block();
        } elseif ($example["status"] == "play_block") {
            $this->phive_user->playBlock();
        } elseif ($example["status"] == "super_block") {
            $this->phive_user->superBlock(false);
        }

        /**
         * Posts the rollback.
         */
        $rollback_parameters = $this->getFundsParameters([
            "canceltxnid" => $bet_parameters['txnid'],
            "amt" => abs($bet_parameters['amt']),
        ]);
        $rollback_request = $this->getFundsRequest($rollback_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $rollback_request);

        $this->unblockPhiveUser();

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the rollback.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("0", $this->getXmlAttribute($this->response, "err"));

        $balance_after_rollback = bcmul(100, $this->getXmlAttribute($this->response, "amt"));
        $I->assertEquals($balance_before_bet, $balance_after_rollback);

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_before_bet, $db_user['cash_balance']);
    }

    /**
     * @param ApiTester $I
     */
    public function rollbackWinNotAllowed(ApiTester $I)
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
            "amt" => "2.50",
            "handid" => $bet_parameters["handid"],
        ]);
        $win_request = $this->getFundsRequest($win_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $win_request);
        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $balance_after_win = $db_user['cash_balance'];

        /**
         * Posts the rollback.
         */
        $rollback_parameters = $this->getFundsParameters([
            "canceltxnid" => $win_parameters['txnid'],
            "amt" => abs($win_parameters['amt']),
        ]);
        $rollback_request = $this->getFundsRequest($rollback_parameters);

        $I->haveHttpHeader('Content-Type', 'application/xml; charset=UTF-8');
        $I->sendPOST("/swintt.php", $rollback_request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $this->response = $I->grabResponse();
        $I->assertNotNull($this->response);
        $this->response = simplexml_load_string($this->response);

        /**
         * Validates the rollback.
         */
        $I->assertEquals("fundTransferResp", $this->getXmlAttribute($this->response, "type"));
        $I->assertEquals("9999", $this->getXmlAttribute($this->response, "err"));

        $db_user = $this->sql_module->sh($this->user_identifier)->fetchResult("SELECT * FROM users WHERE id = {$this->user_identifier}");
        $I->assertEquals($balance_after_win, $db_user['cash_balance']);
    }
}
