<?php

namespace Tests\Api\Phive\Modules\Micro\Thunderkick;

use \ApiTester;
use \Codeception\Example;
use \Codeception\Util\HttpCode;

/**
 * Class BaseBetCest
 * @package Tests\Api\Phive\Modules\Micro\Thunderkick
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Thunderkick/BetCest
 */
class BaseBetCest
{
    protected $game_provider_module;
    protected $sql_module;

    protected $bet_request;
    protected $bet_response;

    /**
     * BetCest constructor.
     * @param null $phive_thunderkick_module
     * @param null $phive_sql_module
     */
    public function __construct($phive_thunderkick_module = null, $phive_sql_module = null)
    {
        $this->game_provider_module = $phive_thunderkick_module ?: phive('Thunderkick');
        $this->sql_module = $phive_sql_module ?: phive('SQL');

        $this->game_provider_module->setDefaults();
    }

    /**
     * @param ApiTester $I
     * @param array $request
     */
    protected function bet(ApiTester $I, array $request)
    {
        $this->bet_request = array_diff_key($request, array_flip(['_label']));
        $this->bet_request = $this->setPlayerSession($I, $this->bet_request);
        $this->bet_request = $this->optionallySetFreeSpinBonusId($this->bet_request, 'bets');

//        $I->amHttpAuthenticated($this->httpAuthenticationUser, $this->httpAuthenticationPassword);
        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $bet_transaction_id = uniqid();
        $I->sendPOST("/thunderkick.php/bet/{$bet_transaction_id}", json_encode($this->bet_request));
        $this->bet_response = json_decode($I->grabResponse(), true);
    }

    /**
     * @param ApiTester $I
     * @param array $request
     * @return array
     */
    protected function setPlayerSession(ApiTester $I, array $request): array
    {
        $session_id = $this->game_provider_module->getGuidv4();
        $this->game_provider_module->toSession($session_id, $request['playerId'] ?? 0, $request['gameRound']['gameName'] ?? 0);

        // Verify the session exists.
        $session = $this->game_provider_module->fromSession($session_id);
        $I->assertIsObject($session);
        $I->assertEquals($session->userid ?? 0, $request['playerId'] ?? 0);

        $request["operatorSessionToken"] = $session_id;
        return $request;
    }

    /**
     * Optionally sets the free bonus spin ID for this player/game if it's a free spin.
     *
     * @param array $request. Bet or win request.
     * @param string $requestKey
     * @return array
     */
    protected function optionallySetFreeSpinBonusId(array $request, string $requestKey = 'bets'): array
    {
        if (!($request["playerId"] ?? false) || !($request["gameRound"]["gameName"] ?? false)) {
            return $request;
        }
        if (!is_array($request[$requestKey] ?? null) || empty($request[$requestKey])) {
            return $request;
        }

        $free_spin_bonus_id = null;
        foreach ($request[$requestKey] as &$bet) {
            if (($bet["accountType"] ?? '') == 'FREE_ROUND') {
                if ($free_spin_bonus_id == null) {
                    $free_spin_bonus_id = $this->getFreeSpinBonusID($request["playerId"] ?? 0, $request["gameRound"]["gameName"] ?? '');
                }
                $bet["accountId"] = "{$request["playerId"]}-{$free_spin_bonus_id}";
            }
        }

        return $request;
    }

    /**
     * Returns the db.bonus_entries.id or null if not found.
     *
     * @param int|null $player_id
     * @param string|null $game_code
     * @return int|null db.bonus_entries.id or null if not found
     */
    protected function getFreeSpinBonusId(int $player_id = null, string $game_code = null)
    {
        $game_id = $this->sql_module->escape($game_code ? "thunderkick_{$game_code}" : "");
        $player_id = $player_id ?: 0;
        $date = $this->sql_module->escape(date('Y-m-d'));

        $sql = <<<EOS
SELECT
    bonus_entries.id
FROM bonus_entries
INNER JOIN bonus_types ON bonus_entries.bonus_id = bonus_types.id 
WHERE 
bonus_types.game_id = {$game_id}
AND bonus_entries.user_id = {$player_id}
AND bonus_entries.frb_remaining > 0
AND bonus_entries.status = 'approved'
AND bonus_entries.start_time <= {$date}
AND bonus_entries.end_time >= {$date}
ORDER BY bonus_entries.id DESC
EOS;
        $db_row = $this->sql_module->sh($player_id)->loadAssoc($sql, null, null, true);
        return $db_row['id'] ?? null;
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataValidBet
     */
    protected function validBet(ApiTester $I, Example $example)
    {
        $this->bet($I, iterator_to_array($example));

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            "balances" => "array",
            "extBetTransactionId" => "string",
        ]);
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataInvalidBet
     */
    protected function invalidBet(ApiTester $I, Example $example)
    {
        $this->bet($I, iterator_to_array($example));

        $I->seeResponseCodeIs(HttpCode::METHOD_NOT_ALLOWED);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            "errorCode" => "string",
            "errorMessage" => "string",
        ]);

        $I->seeResponseContainsJson([
            "errorCode" => "ER02",
            "errorMessage" => "Command not found.",
        ]);
    }

    /**
     * @return array
     */
    protected function defaultBetRequest()
    {
        $player_id = "5541343";     // devtestmt

        return [
            "_label" => "bet 1.00",
            "playerId" => $player_id,
            "operatorSessionToken" => "",
            "distributionChannel" => "WEB",

            "bets" => [
                [
                    "bet" => [
                        "amount" => "1",
                        "currency" => "EUR",
                    ],
                    "accountId" => $player_id,
                    "accountType" => "REAL",
                ],
            ],
            "gameRound" => [
                "gameName" => "tk-magicians-a",
                "gameRoundId" => rand(10000, 1000000),
            ],
        ];
    }

    /**
     * Data provider.
     *
     * @return array
     */
    protected function dataValidBet(): array
    {
        $player_id = "5541343";     // devtestmt

        return [
            [
                "_label" => "bet",
                "playerId" => $player_id,
                "operatorSessionToken" => "",
                "distributionChannel" => "WEB",

                "bets" => [
                    [
                        "bet" => [
                            "amount" => "1",
                            "currency" => "EUR",
                        ],
                        "accountId" => $player_id,
                        "accountType" => "REAL",
                    ],
                ],
                "gameRound" => [
                    "gameName" => "tk-magicians-a",
                    "gameRoundId" => rand(10000, 1000000),
                ],
            ],
        ];
    }

    /**
     * Data provider.
     *
     * @return array
     */
    protected function dataInvalidBet(): array
    {
        $player_id = "5541343";     // devtestmt

        return [
            [
                "_label" => "missing bet data",
                "playerId" => $player_id,
                "operatorSessionToken" => "",
                "distributionChannel" => "WEB",
                "gameName" => "tk-magicians-a",

                /**
                 * This request is unexpectedly, and probably incorrectly, returning a successful response for
                 * the Bet API if the key is called 'win' (instead of 'XXbets').
                 */
                "XXbets" => [
                    [
                        "win" => [
                            "amount" => "1",
                            "currency" => "EUR",
                        ],
                        "accountId" => $player_id,
                        "accountType" => "REAL",
                    ],
                ],
                "gameRound" => [
                    "gameName" => "tk-magicians-a",
                    "gameRoundId" => rand(10000, 1000000),
                ],
            ],
        ];
    }
}
