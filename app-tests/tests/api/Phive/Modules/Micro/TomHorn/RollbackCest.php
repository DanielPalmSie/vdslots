<?php

namespace Tests\Api\Phive\Modules\Micro\TomHorn;

require_once __DIR__ . '/BaseCest.php';

use \ApiTester;

/**
 * Class RollbackCest
 *
 * Tests Tom Horn rollbacks.
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/TomHorn/RollbackCest
 */
class RollbackCest extends BaseCest
{
    /**
     * @var array $bet_request
     */
    private $bet_request;

    /**
     * @var array $bet_response
     */
    private $bet_response;

    /**
     * @var int $balance_before_bet
     */
    private $balance_before_bet;

    /**
     * @var int $balance_after_bet
     */
    private $balance_after_bet;

    /**
     * @var array $bet_request
     */
    private $win_request;

    /**
     * @var array $bet_response
     */
    private $win_response;

    /**
     * @var int $balance_before_win
     */
    private $balance_before_win;

    /**
     * @var int $balance_after_win
     */
    private $balance_after_win;

    /**
     * DepositCest constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->url = "/tomhorn.php/RollbackTransaction";
    }

    /**
     * @param ApiTester $I
     */
    public function createBet(ApiTester $I)
    {
        $this->bet_request = $this->newRequest();

        $this->tomhorn->setExternalSessionId($this->bet_request["sessionID"], $this->bet_request["name"], $this->bet_request["gameModule"]);

        // Clears the player's status.
        $this->dbUser->setAttribute('active', 1);
        $this->dbUser->deleteSetting('play_block');
        $this->dbUser->deleteSetting('super-blocked');

        $this->balance_before_bet = $this->dbUser->getAttr('cash_balance', true);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST("/tomhorn.php/Withdraw", json_encode($this->bet_request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $this->bet_response = json_decode($I->grabResponse(), true);
        $this->balance_after_bet = $this->dbUser->getAttr('cash_balance', true);
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
        ]);
    }

    /**
     * @param ApiTester $I
     */
    public function createWin(ApiTester $I)
    {
        $this->win_request = $this->newRequest(array_merge($this->bet_request, [
            'reference' => $this->bet_request['reference'] + 1,
        ]));
        $this->balance_before_win = $this->dbUser->getAttr('cash_balance', true);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST("/tomhorn.php/Deposit", json_encode($this->win_request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
        ]);
        $this->win_response = json_decode($I->grabResponse(), true);
        $this->balance_after_win = $this->dbUser->getAttr('cash_balance', true);
    }

    /**
     * @param ApiTester $I
     *
     * @before createBet
     */
    public function rollbackBetUpdatesUserBalance(ApiTester $I)
    {
        $rollbackRequest = $this->newRequest(
            ['reference' => $this->bet_request['reference']],
            ['amount', 'currency', 'gameRoundID', 'gameModule', 'type', 'fgbCampaignCode']
        );

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($rollbackRequest));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
        ]);

        $balance_after_rollback = $this->dbUser->getAttr('cash_balance', true);
        $I->assertEquals($this->balance_before_bet, $balance_after_rollback);
    }

    /**
     * @param ApiTester $I
     *
     * @before createBet
     * @before createWin
     */
    public function rollbackWinUpdatesUserBalance(ApiTester $I)
    {
        $rollbackRequest = $this->newRequest(
            ['reference' => $this->win_request['reference']],
            ['amount', 'currency', 'gameRoundID', 'gameModule', 'type', 'fgbCampaignCode']
        );

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($rollbackRequest));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
        ]);

        $balance_after_rollback = $this->dbUser->getAttr('cash_balance', true);
        $I->assertEquals($this->balance_before_win, $balance_after_rollback);
    }

    /**
     * @param ApiTester $I
     *
     * @before createBet
     */
    public function handlesDuplicateRollbackBet(ApiTester $I)
    {
        $rollbackRequest = $this->newRequest(
            ['reference' => $this->bet_request['reference']],
            ['amount', 'currency', 'gameRoundID', 'gameModule', 'type', 'fgbCampaignCode']
        );

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($rollbackRequest));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
        ]);

        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM users WHERE id = {$this->userId}");
        $I->assertEquals($this->balance_before_bet, $row['cash_balance']);

        /**
         * Duplicates the rollback request.
         */
        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($rollbackRequest));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "Code" => 9,
            "Message" => "Specified transaction was already rolled back",
        ]);

        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM users WHERE id = {$this->userId}");
        $I->assertEquals($this->balance_before_bet, $row['cash_balance']);
    }

    /**
     * @param ApiTester $I
     * @param \Codeception\Example $example
     *
     * @example { "reference": null }
     * @example { "reference": "0" }
     */
    public function handlesInvalidTransactionReference(ApiTester $I, \Codeception\Example $example)
    {
        $request = $this->newRequest($example);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            "Code" => 2,
            "Message" => "Missing request parameter: reference.",
        ]);
    }

    /**
     * @param ApiTester $I
     * @param \Codeception\Example $example
     *
     * @example { "reference": "1" }
     * @example { "reference": "xxx" }
     */
    public function handlesUnknownTransactionReference(ApiTester $I, \Codeception\Example $example)
    {
        $request = $this->newRequest($example);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseContainsJson([
            "Code" => 12,
            "Message" => "Transaction with the specified reference hasn't ever been recorded.",
        ]);
    }

    /**
     * @param ApiTester $I
     * @param \Codeception\Example $example
     *
     * @before createBet
     *
     * @example { "status": "block", "message": "Player is banned." }
     * @example { "status": "play_block", "message": "Player is banned." }
     * @example { "status": "super_block", "message": "Player is blocked." }
     */
    public function blockedPlayerCanRollbackBet(ApiTester $I, \Codeception\Example $example)
    {
        /**
         * Blocks the player after making a bet.
         */
        if ($example["status"] == "block") {
            $this->dbUser->block();
        } elseif ($example["status"] == "play_block") {
            $this->dbUser->playBlock();
        } elseif ($example["status"] == "super_block") {
            $this->dbUser->superBlock(false);
        }

        $rollbackRequest = $this->newRequest(
            ['reference' => $this->bet_request['reference']],
            ['amount', 'currency', 'gameRoundID', 'gameModule', 'type', 'fgbCampaignCode']
        );

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($rollbackRequest));

        // Restores the player's status.
        $this->dbUser->setAttribute('active', 1);
        $this->dbUser->deleteSetting('play_block');
        $this->dbUser->deleteSetting('super-blocked');

        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
        ]);

        $balance_after_rollback = $this->dbUser->getAttr('cash_balance', true);
        $I->assertEquals($this->balance_before_bet, $balance_after_rollback);
    }
}
