<?php

namespace Tests\Api\Phive\Modules\Micro\Thunderkick;

require_once __DIR__ . '/BaseBetCest.php';

use \ApiTester;
use \Codeception\Example;
use \Codeception\Util\HttpCode;

/**
 * Class WinCest
 * @package Tests\Api\Phive\Modules\Micro\Thunderkick
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Thunderkick/WinCest:validWinWithBet
 */
class WinCest extends BaseBetCest
{
    protected $win_request;
    protected $win_response;

    /**
     * @param ApiTester $I
     * @param array $bet_request
     * @param array $win_request
     */
    protected function win(ApiTester $I, array $bet_request, array $win_request)
    {
        if (!empty($bet_request)) {
            $this->bet($I, $bet_request);
        }

        $this->win_request = array_diff_key($win_request, array_flip(['_label']));
        if (empty($bet_request)) {
            $this->win_request = $this->setPlayerSession($I, $this->win_request);
        } else {
            $this->win_request["operatorSessionToken"] = $this->bet_request["operatorSessionToken"];
        }

        $this->win_request = $this->optionallySetFreeSpinBonusId($this->win_request, 'wins');

        $I->haveHttpHeader('Content-Type', 'application/json; charset=UTF-8');
        $win_transaction_id = uniqid();
        $I->sendPOST("/thunderkick.php/win/{$win_transaction_id}", json_encode($this->win_request));
        $this->win_response = json_decode($I->grabResponse(), true);
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataValidWin
     */
    public function validWinWithBet(ApiTester $I, Example $example)
    {
        $bet_request = $this->defaultBetRequest();
        $win_request = iterator_to_array($example);

        $this->win($I, $bet_request, $win_request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            "balances" => "array",
            "extWinTransactionId" => "string",
        ]);
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataValidWin
     */
    public function validWinWithoutBet(ApiTester $I, Example $example)
    {
        $bet_request = [];
        $win_request = iterator_to_array($example);

        $this->win($I, $bet_request, $win_request);

        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();

        $I->seeResponseMatchesJsonType([
            "balances" => "array",
            "extWinTransactionId" => "string",
        ]);
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
                "_label" => "win",
                "playerId" => $player_id,
                "operatorSessionToken" => "",
                "distributionChannel" => "WEB",

                "wins" => [
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

            [
                "_label" => "win_with_new_api_field",
                "playerId" => $player_id,
                "operatorSessionToken" => "",
                "distributionChannel" => "WEB",
                "gameName" => "tk-magicians-a",

                "wins" => [
                    [
                        "win" => [
                            "amount" => "1",
                            "currency" => "EUR",
                        ],
                        "accountId" => $player_id,
                        "accountType" => "REAL",

                        "initialNumberOfFreeRounds" => 10,
                        "remainingNumberOfFreeRounds" => 3,
                    ],
                ],
                "gameRound" => [
                    "gameName" => "tk-magicians-a",
                    "gameRoundId" => rand(10000, 1000000),
                ],
            ],

//            [
//                "_label" => "free spin win",
//                "playerId" => $this->playerId,
//                "operatorSessionToken" => "",
//                "distributionChannel" => "WEB",
//
//                "wins" => [
//                    [
//                        "win" => [
//                            "amount" => "1",
//                            "currency" => $this->currency,
//                        ],
//                        "accountId" => "{$this->playerId}-{$freeSpinBonusID}",
//                        "accountType" => "FREE_ROUND",
//                    ],
//                ],
//                "gameRound" => [
//                    "gameName" => $this->gameCode,
//                    "gameRoundId" => $gameRoundID,
//                ],
//            ],
//
//            [
//                "_label" => "new free spin win",
//                "playerId" => $this->playerId,
//                "operatorSessionToken" => "",
//                "distributionChannel" => "WEB",
//
//                "wins" => [
//                    [
//                        "win" => [
//                            "amount" => "1",
//                            "currency" => $this->currency,
//                        ],
//                        "accountId" => "{$this->playerId}-{$freeSpinBonusID}",
//                        "accountType" => "FREE_ROUND",
//
//                        "initialNumberOfFreeRounds" => 10,
//                        "remainingNumberOfFreeRounds" => 3,
//                    ],
//                ],
//                "gameRound" => [
//                    "gameName" => $this->gameCode,
//                    "gameRoundId" => $gameRoundID,
//                ],
//            ],
        ];
    }
}