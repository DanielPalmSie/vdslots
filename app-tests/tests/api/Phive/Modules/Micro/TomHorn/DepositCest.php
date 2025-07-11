<?php

namespace Tests\Api\Phive\Modules\Micro\TomHorn;

require_once __DIR__ . '/BaseCest.php';

use \ApiTester;
use \Codeception\Util\HttpCode;

/**
 * Class DepositCest
 *
 * Tests Tom Horn deposits (wins).
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/TomHorn/DepositCest:createValidWin
 */
class DepositCest extends BaseCest
{
    /**
     * @var array $bet_request
     */
    private $bet_request;

    /**
     * @var int $balance_before_bet
     */
    private $balance_before_bet;

    /**
     * @var int $balance_after_bet
     */
    private $balance_after_bet;

    /**
     * @var array $win_request
     */
    private $win_request;

    /**
     * @var array $win_response
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
        $this->url = "/tomhorn.php/Deposit";
    }

    /**
     * @param ApiTester $I
     * @param array|null $request.
     */
    public function createBet(ApiTester $I, array $request = null)
    {
        $this->bet_request = (!empty($request) ? $request : $this->defaultRequest());
        $this->bet_request['sign'] = $this->tomhorn->generateSignFromParams($this->bet_request, $this->bet_request["name"] /* user_id */);

        $this->tomhorn->setExternalSessionId($this->bet_request["sessionID"], $this->bet_request["name"], $this->bet_request["gameModule"]);

        $this->dbUser = (cu($this->bet_request["name"] ?? 0) ?: null);
        $this->balance_before_bet = ($this->dbUser ? $this->dbUser->getAttr('cash_balance', true) : null);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST("/tomhorn.php/Withdraw", json_encode($this->bet_request));

        $this->bet_response = json_decode($I->grabResponse(), true);
        $this->balance_after_bet = ($this->dbUser ? $this->dbUser->getAttr('cash_balance', true) : null);
    }

    /**
     * @param ApiTester $I
     * @param array|null $request
     */
    public function createWin(ApiTester $I, array $request = null)
    {
        $this->createBet($I);

        $this->win_request = array_merge(
            $this->bet_request,
            $request ?: [],
            [
                'amount' => "3.5",
                'reference' => $this->bet_request['reference'] + 1,
            ]
        );
        $this->win_request['sign'] = $this->tomhorn->generateSignFromParams($this->win_request, $this->win_request["name"] /* user_id */);

        $this->dbUser = (cu($this->win_request["name"] ?? 0) ?: null);
        $this->balance_before_win = ($this->dbUser ? $this->dbUser->getAttr('cash_balance', true) : null);

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST("/tomhorn.php/Deposit", json_encode($this->win_request));

        $this->win_response = json_decode($I->grabResponse(), true);
        $this->balance_after_win = ($this->dbUser ? $this->dbUser->getAttr('cash_balance', true) : null);
    }

    /**
     * @param ApiTester $I
     *
     * Usage: php vendor/bin/codecept run api modules/Micro/TomHorn/DepositCest:createValidWin
     */
    public function createValidWin(ApiTester $I)
    {
        $this->createWin($I);

        $I->assertNotNull($this->dbUser);
        $I->assertNotNull($this->balance_before_win);
        $I->assertNotNull($this->balance_after_win);
        $I->assertEquals($this->balance_before_win + intval(100 * $this->win_request['amount']), $this->balance_after_win);

        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
            "Transaction" => [
                "Balance" => round(($this->balance_before_win / 100) + $this->win_request['amount'], 2),
                "Currency" => $this->currency,
            ],
        ]);
    }

    /**
     * Creates a win for the matching bet.
     *
     * @param ApiTester $I
     *
     * @before createValidWin
     */
    public function insertsADbWin(ApiTester $I)
    {
        $transaction_id = $this->win_response['Transaction']['ID'] ?? 0;

        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM wins WHERE id = {$transaction_id}");
        $I->assertNotNull($row);
        $I->assertEquals($this->userId, $row['user_id']);
        $I->assertEquals(intval(100 * $this->win_request['amount']), $row['amount']);
        $I->assertEquals($this->win_request['gameRoundID'], $row['trans_id']);
        $I->assertEquals("tomhorn_{$this->win_request['reference']}", $row['mg_id']);
    }

    /**
     * @param ApiTester $I
     *
     * @before createValidWin
     */
    public function updatesUserBalance(ApiTester $I)
    {
        $I->assertEquals($this->balance_before_win + intval(100 * $this->win_request['amount']), $this->balance_after_win);
    }

    /**
     * @param ApiTester $I
     * @param \Codeception\Example $example
     *
     * @before createBet
     *
     * @example { "reference": null }
     * @example { "reference": "0" }
     */
    public function handlesMissingTransactionReference(ApiTester $I, \Codeception\Example $example)
    {
        $request = $this->newRequest(array_merge($this->bet_request, [
            'amount' => "3.5",
            'reference' => $example['reference'],
        ]));

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
     * This error message will actually never be returned for wins because:
     * 1. If same transaction ID and same game round ID then error message is: { "Code": 1, "Message": "Transaction with specified game round ID was already processed successfully." }
     * 2. If same transaction ID and different game round ID then error message is: { "Code": 1, "Message": "Unknown game round ID." }
     *
     * @param ApiTester $I
     *
     * @before createBet
     */
    private function handlesDuplicateTransaction(ApiTester $I)
    {
        /**
        $I->sendPOST($this->url, json_encode($request)); // 200
        $I->seeResponseContainsJson([
            "Code" => 11,
            "Message" => "Deposit or withdrawal with specified reference was already processed successfully.",
        ]);
        */
    }

    /**
     * @param ApiTester $I
     *
     * @before createBet
     */
    public function handlesDuplicateGameRoundId(ApiTester $I)
    {
        $request = $this->newRequest(array_merge($this->bet_request, [
            'amount' => "3.5",
            'reference' => $this->bet_request['reference'] + 1,
        ]));

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
        ]);

        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM users WHERE id = {$this->userId}");
        $balance = $row['cash_balance'];

        $request2 = $this->newRequest(array_merge($this->bet_request, [
            'amount' => "3.5",
            'reference' => $this->bet_request['reference'] + 2,
        ]));

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request2));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 11,
            "Message" => "Transaction with specified game round ID was already processed successfully.",
        ]);

        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM users WHERE id = {$this->userId}");
        $I->assertEquals($balance, $row['cash_balance']);
    }

    /**
     * @param ApiTester $I
     *
     * @before createBet
     *
     * @example { "amount": null }
     * @example { "amount": "0" }
     * @example { "amount": "-5" }
     */
    public function handlesInvalidAmount(ApiTester $I, \Codeception\Example $example)
    {
        $request = $this->newRequest(array_merge($this->bet_request, [
            'amount' => $example['amount'],
            'reference' => $this->bet_request['reference'] + 1,
        ]));

        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM users WHERE id = {$this->userId}");
        $balance = $row['cash_balance'];

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();

        $expected_error_message = "Missing request parameter: amount.";
        if (is_numeric($example["amount"]) && $example["amount"]) {
            $expected_error_message = "Invalid request parameter: amount.";
        }

        $I->seeResponseContainsJson([
            "Code" => 2,
            "Message" => $expected_error_message,
        ]);

        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM users WHERE id = {$this->userId}");
        $I->assertEquals($balance, $row['cash_balance']);
    }

    /**
     * @param ApiTester $I
     *
     * @before createBet
     */
    public function handlesNoMatchingBet(ApiTester $I)
    {
        $request = $this->newRequest(array_merge($this->bet_request, [
            'amount' => "3.5",
            'gameRoundID' => 1,
        ]));

        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM users WHERE id = {$this->userId}");
        $balance = $row['cash_balance'];

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 12,
            "Message" => "Bet not found for this round.",
        ]);

        // Verifies the balance was not updated.
        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM users WHERE id = {$this->userId}");
        $I->assertEquals($balance, $row['cash_balance']);
    }

    /**
     * @param ApiTester $I
     *
     * @before createBet
     */
    public function handlesUnknownGame(ApiTester $I)
    {
        $request = $this->newRequest(array_merge($this->bet_request, ['gameModule' => 'XXX']));

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 2,
            "Message" => "Game is not found.",
        ]);
    }

    /**
     * @param ApiTester $I
     *
     * @before createBet
     */
    public function handlesUnknownUser(ApiTester $I)
    {
        $request = $this->newRequest(array_merge($this->bet_request, ['name' => 1]));

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 5,
            "Message" => "Cannot find specified identity.",
        ]);
    }

    /**
     * @param ApiTester $I
     * @param \Codeception\Example $example
     *
     * @before createBet
     *
     * @example { "status": "block", "message": "Player is inactive." }
     * @example { "status": "play_block", "message": "Player is blocked." }
     * @example { "status": "super_block", "message": "Player is banned." }
     */
    public function blockedPlayerCannotMakeWin(ApiTester $I, \Codeception\Example $example)
    {
        $I->assertNotNull($this->dbUser);

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

        $this->win_request = $this->newRequest(array_merge($this->bet_request, [
            'amount' => "3.5",
            'reference' => $this->bet_request['reference'] + 1,
        ]));

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $I->sendPOST($this->url, json_encode($this->win_request));
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 1,
            "Message" => $example["message"],
        ]);

        // Restores the player's status.
        $this->dbUser->setAttribute('active', 1);
        $this->dbUser->deleteSetting('play_block');
        $this->dbUser->deleteSetting('super-blocked');
    }
}
