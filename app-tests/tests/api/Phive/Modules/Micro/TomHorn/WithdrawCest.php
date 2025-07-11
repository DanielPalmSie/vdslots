<?php

namespace Tests\Api\Phive\Modules\Micro\TomHorn;

require_once __DIR__ . '/BaseCest.php';

use \ApiTester;
use \Codeception\Util\HttpCode;

/**
 * Class WithdrawCest
 *
 * Tests Tom Horn withdrawals (bets).
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/TomHorn/WithdrawCest:createValidBet
 */
class WithdrawCest extends BaseCest
{
    /**
     * @var array $bet_request
     */
    private $bet_request;

    /**
     * @var array $bet_response
     */
    private $bet_response;

    private $balance_before_bet;

    private $balance_after_bet;

    /**
     * WithdrawCest constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->url = "/tomhorn.php/Withdraw";
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
     */
    public function createValidBet(ApiTester $I)
    {
        $this->createBet($I);

        $I->assertNotNull($this->dbUser);
        $I->assertNotNull($this->balance_before_bet);
        $I->assertNotNull($this->balance_after_bet);
        $I->assertEquals($this->balance_before_bet - intval(100 * $this->bet_request['amount']), $this->balance_after_bet);

        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 0,
            "Message" => "",
            "Transaction" => [
                "Balance" => ($this->balance_before_bet / 100) - $this->bet_request['amount'],
                "Currency" => $this->currency,
            ],
        ]);
    }

    /**
     * @param ApiTester $I
     *
     * @before createValidBet
     */
    public function insertsADbBet(ApiTester $I)
    {
        $transaction_id = $this->bet_response['Transaction']['ID'] ?? 0;

        $row = phive('SQL')->sh($this->userId)->fetchResult("SELECT * FROM bets WHERE id = {$transaction_id}");
        $I->assertNotNull($row);
        $I->assertEquals($this->userId, $row['user_id']);
        $I->assertEquals(intval(100 * $this->bet_request['amount']), $row['amount']);
        $I->assertEquals($this->bet_request['gameRoundID'], $row['trans_id']);
        $I->assertEquals("tomhorn_{$this->bet_request['reference']}", $row['mg_id']);
    }

    /**
     * @param ApiTester $I
     */
    public function handlesUnknownUser(ApiTester $I)
    {
        $bet_request = array_merge($this->defaultRequest(), ['name' => 1]);
        $this->createBet($I, $bet_request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 5,
            "Message" => "Cannot find specified identity.",
        ]);
    }

    /**
     * @param ApiTester $I
     */
    public function handlesUnknownGame(ApiTester $I)
    {
        $bet_request = array_merge($this->defaultRequest(), ['gameModule' => 'XXX']);
        $this->createBet($I, $bet_request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 2,
            "Message" => "Game is not found.",
        ]);
    }

    /**
     * @param ApiTester $I
     */
    public function handlesDuplicateTransaction(ApiTester $I)
    {
        $this->createValidBet($I);
        $balance_after_first_bet = $this->balance_after_bet;

        $request = array_merge($this->defaultRequest(), ['reference' => $this->bet_request['reference']]);
        $this->createBet($I, $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 11,
            "Message" => "Deposit or withdrawal with specified reference was already processed successfully.",
        ]);

        $I->assertEquals($balance_after_first_bet, $this->balance_after_bet);
    }

    /**
     * @param ApiTester $I
     */
    public function handlesDuplicateGameRoundId(ApiTester $I)
    {
        $this->createValidBet($I);
        $balance_after_first_bet = $this->balance_after_bet;

        $request = array_merge($this->defaultRequest(), ['gameRoundID' => $this->bet_request['gameRoundID']]);
        $this->createBet($I, $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 1,
            "Message" => "Round already exists in database.",
        ]);

        $I->assertEquals($balance_after_first_bet, $this->balance_after_bet);
    }

    /**
     * @param ApiTester $I
     */
    public function handlesInsufficientFunds(ApiTester $I)
    {
        $request = array_merge($this->defaultRequest(), ['amount' => '1000000']);
        $this->createBet($I, $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 6,
            "Message" => "Insufficient funds available to complete the transaction.",
        ]);
    }

    /**
     * @param ApiTester $I
     * @param \Codeception\Example $example
     *
     * @example { "reference": null }
     * @example { "reference": "0" }
     */
    public function handlesMissingTransactionReference(ApiTester $I, \Codeception\Example $example)
    {
        $request = array_merge($this->defaultRequest(), ['reference' => $example["reference"]]);
        $this->createBet($I, $request);

        $I->seeResponseCodeIs(HttpCode::OK);
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
     * @example { "amount": null }
     * @example { "amount": "0" }
     * @example { "amount": "-5" }
     * @example { "amount": "XXX" }
     */
    public function handlesInvalidAmount(ApiTester $I, \Codeception\Example $example)
    {
        $request = array_merge($this->defaultRequest(), ['amount' => $example["amount"]]);
        $this->createBet($I, $request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $expected_error_message = "Missing request parameter: amount.";
        if (is_numeric($example["amount"]) && $example["amount"]) {
            $amount = 100 * $example["amount"];
            $expected_error_message = "Invalid amount ({$amount}).";
        }

        $I->seeResponseContainsJson([
            "Code" => 2,
            "Message" => $expected_error_message,
        ]);
    }

    /**
     * @param ApiTester $I
     * @param \Codeception\Example $example
     *
     * @example { "status": "block", "message": "Player is inactive." }
     * @example { "status": "play_block", "message": "Player is blocked." }
     * @example { "status": "super_block", "message": "Player is banned." }
     */
    public function blockedPlayerCannotMakeBet(ApiTester $I, \Codeception\Example $example)
    {
        $this->dbUser = cu($this->userId);
        $I->assertNotNull($this->dbUser);

        if ($example["status"] == "block") {
            $this->dbUser->block();
        } elseif ($example["status"] == "play_block") {
            $this->dbUser->playBlock();
        } elseif ($example["status"] == "super_block") {
            $this->dbUser->superBlock(false);
        }

        $this->createBet($I);

        // Restores the player's status.
        $this->dbUser->setAttribute('active', 1);
        $this->dbUser->deleteSetting('play_block');
        $this->dbUser->deleteSetting('super-blocked');

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            "Code" => 1,
            "Message" => $example["message"],
        ]);

        $I->assertEquals($this->balance_before_bet, $this->balance_after_bet);
    }
}
