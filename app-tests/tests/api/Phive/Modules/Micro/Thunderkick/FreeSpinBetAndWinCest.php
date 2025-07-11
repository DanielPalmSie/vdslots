<?php

namespace Tests\Api\Phive\Modules\Micro\Thunderkick;

require_once __DIR__ . '/BaseBetCest.php';

use \ApiTester;
use \Codeception\Example;
use \Codeception\Util\HttpCode;

/**
 * !!! IMPORTANT !!!
 *
 * Verify that a free spin reward exists for the player/game, is activated, not expired and has remaining free spins.
 *
 * Class FreeSpinBetAndWinCest
 * @package Tests\Api\Phive\Modules\Micro\Thunderkick
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Thunderkick/FreeSpinBetAndWinCest:validBet
 */
class FreeSpinBetAndWinCest extends BaseBetCest
{
    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataValidBet
     */
    public function validBet(ApiTester $I, Example $example)
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
     * @dataProvider dataValidWin
     */
    public function validWin(ApiTester $I, Example $example)
    {
        $request = array_diff_key(iterator_to_array($example), array_flip(['_label']));
        $request = $this->setPlayerSession($I, $request);

        $request = $this->optionallySetFreeSpinBonusId($request, 'wins');

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $transaction_id = uniqid();
        $I->sendPOST("/thunderkick.php/win/{$transaction_id}", json_encode($request));
        $response = json_decode($I->grabResponse(), true);

        $I->seeResponseMatchesJsonType([
            "balances" => "array",
            "extWinTransactionId" => "string",
        ]);
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataValidBetAndWin
     */
    public function validBetAndWin(ApiTester $I, Example $example)
    {
        $request = iterator_to_array($example);
        $request = array_diff_key($request, array_flip(['_label']));
        $request = $this->setPlayerSession($I, $request);

        $request = $this->optionallySetFreeSpinBonusId($request, 'bets');
        $request = $this->optionallySetFreeSpinBonusId($request, 'wins');

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $transaction_id = uniqid();
        $I->sendPOST("/thunderkick.php/betAndWin/{$transaction_id}", json_encode($request));

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            "balances" => [
                "moneyAccounts" => "array",
            ],
            "extBetTransactionId" => "string",
            "extWinTransactionId" => "string",
        ]);
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
                "_label" => "free spin bet",
                "playerId" => $player_id,
                "operatorSessionToken" => "",
                "distributionChannel" => "WEB",

                "bets" => [
                    [
                        "bet" => [
                            "amount" => "0",
                            "currency" => "EUR",
                        ],
                        "accountId" => "{$player_id}-0",
                        "accountType" => "FREE_ROUND",
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
    protected function dataValidWin(): array
    {
        $player_id = "5541343";     // devtestmt

        return [
            [
                "_label" => "free spin win",
                "playerId" => $player_id,
                "operatorSessionToken" => "",
                "distributionChannel" => "WEB",

                "wins" => [
                    [
                        "win" => [
                            "amount" => "1",
                            "currency" => "EUR",
                        ],
                        "accountId" => "{$player_id}-0",
                        "accountType" => "FREE_ROUND",
                    ],
                ],
                "gameRound" => [
                    "gameName" => "tk-magicians-a",
                    "gameRoundId" => rand(10000, 1000000),
                ],
            ],

            [
                "_label" => "new free spin win",
                "playerId" => $player_id,
                "operatorSessionToken" => "",
                "distributionChannel" => "WEB",

                "wins" => [
                    [
                        "win" => [
                            "amount" => "1",
                            "currency" => "EUR",
                        ],
                        "accountId" => "{$player_id}-0",
                        "accountType" => "FREE_ROUND",

                        "initialNumberOfFreeRounds" => 10,
                        "remainingNumberOfFreeRounds" => 3,
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
    protected function dataValidBetAndWin(): array
    {
        $player_id = "5541343";     // devtestmt
        $currency = "EUR";
        $game_code = "tk-magicians-a";

        return [
            [
                "_label" => "free bet and win",
                "playerId" => $player_id,
                "operatorSessionToken" => "",
                "distributionChannel" => "WEB",

                "gameRound" => [
                    "gameName" => $game_code,
                    "gameRoundId" => rand(10000, 1000000),

                    "numberOfBets" => 1,
                    "numberOfWins" => 1,
                ],

                "bets" => [
                    [
                        "bet" => [
                            "amount" => "0",
                            "currency" => $currency,
                        ],
                        "accountId" => "{$player_id}-0",
                        "accountType" => "FREE_ROUND",
                    ],
                ],

                "wins" => [
                    [
                        "win" => [
                            "amount" => "1",
                            "currency" => $currency,
                        ],
                        "accountId" => "{$player_id}-0",
                        "accountType" => "FREE_ROUND",
                    ],
                ],
            ],

            [
                "_label" => "new free bet and win",
                "playerId" => $player_id,
                "operatorSessionToken" => "",
                "distributionChannel" => "WEB",

                "gameRound" => [
                    "gameName" => $game_code,
                    "gameRoundId" => rand(10000, 1000000),

                    "numberOfBets" => 1,
                    "numberOfWins" => 1,
                ],

                "bets" => [
                    [
                        "bet" => [
                            "amount" => "0",
                            "currency" => $currency,
                        ],
                        "accountId" => "{$player_id}-0",
                        "accountType" => "FREE_ROUND",
                    ],
                ],

                "wins" => [
                    [
                        "win" => [
                            "amount" => "1",
                            "currency" => $currency,
                        ],
                        "accountId" => "{$player_id}-0",
                        "accountType" => "FREE_ROUND",

                        "initialNumberOfFreeRounds" => 10,
                        "remainingNumberOfFreeRounds" => 3,
                    ],
                ],
            ],
        ];
    }
}