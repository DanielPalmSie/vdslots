<?php

namespace Tests\Api\Phive\Modules\Micro\Thunderkick;

require_once __DIR__ . '/BaseBetCest.php';

use \ApiTester;
use \Codeception\Example;
use \Codeception\Util\HttpCode;

/**
 * Class BetAndWinCest
 * @package Tests\Api\Phive\Modules\Micro\Thunderkick
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Thunderkick/BetAndWinCest:validBetAndWin
 */
class BetAndWinCest extends BaseBetCest
{
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
    protected function dataValidBetAndWin(): array
    {
        $player_id = "5541343";     // devtestmt
        $currency = "EUR";
        $game_code = "tk-magicians-a";

        return [
            [
                "_label" => "betwin",
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
                            "amount" => "1",
                            "currency" => $currency,
                        ],
                        "accountId" => $player_id,
                        "accountType" => "REAL",
                    ],
                ],

                "wins" => [
                    [
                        "win" => [
                            "amount" => "1",
                            "currency" => $currency,
                        ],
                        "accountId" => $player_id,
                        "accountType" => "REAL",
                    ],
                ],
            ],

            [
                "_label" => "new betwin",
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
                            "amount" => "1",
                            "currency" => $currency,
                        ],
                        "accountId" => $player_id,
                        "accountType" => "REAL",
                    ],
                ],

                "wins" => [
                    [
                        "win" => [
                            "amount" => "3",
                            "currency" => $currency,
                        ],
                        "accountId" => $player_id,
                        "accountType" => "REAL",

                        "initialNumberOfFreeRounds" => 10,
                        "remainingNumberOfFreeRounds" => 3,
                    ],
                ],
            ],

//            [
//                "_label" => "free betwin",
//                "playerId" => $player_id,
//                "operatorSessionToken" => "",
//                "distributionChannel" => "WEB",
//
//                "gameRound" => [
//                    "gameName" => $game_code,
//                    "gameRoundId" => rand(10000, 1000000),
//
//                    "numberOfBets" => 1,
//                    "numberOfWins" => 1,
//                ],
//
//                "bets" => [
//                    [
//                        "bet" => [
//                            "amount" => "0",
//                            "currency" => $currency,
//                        ],
//                        "accountId" => "{$player_id}-0",
//                        "accountType" => "FREE_ROUND",
//                    ],
//                ],
//
//                "wins" => [
//                    [
//                        "win" => [
//                            "amount" => "1",
//                            "currency" => $currency,
//                        ],
//                        "accountId" => "{$player_id}-0",
//                        "accountType" => "FREE_ROUND",
//                    ],
//                ],
//            ],

//            [
//                "_label" => "new free betwin",
//                "playerId" => $player_id,
//                "operatorSessionToken" => "",
//                "distributionChannel" => "WEB",
//
//                "gameRound" => [
//                    "gameName" => $game_code,
//                    "gameRoundId" => rand(10000, 1000000),
//
//                    "numberOfBets" => 1,
//                    "numberOfWins" => 1,
//                ],
//
//                "bets" => [
//                    [
//                        "bet" => [
//                            "amount" => "0",
//                            "currency" => $currency,
//                        ],
//                        "accountId" => "{$player_id}-0",
//                        "accountType" => "FREE_ROUND",
//                    ],
//                ],
//
//                "wins" => [
//                    [
//                        "win" => [
//                            "amount" => "1",
//                            "currency" => $currency,
//                        ],
//                        "accountId" => "{$player_id}-0",
//                        "accountType" => "FREE_ROUND",
//
//                        "initialNumberOfFreeRounds" => 10,
//                        "remainingNumberOfFreeRounds" => 3,
//                    ],
//                ],
//            ],
        ];
    }
}