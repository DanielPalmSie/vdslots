<?php

namespace Tests\Api\Phive\Modules\Micro\Thunderkick;

require_once __DIR__ . '/BaseBetCest.php';

use \ApiTester;
use \Codeception\Example;

/**
 * Class BetCest
 * @package Tests\Api\Phive\Modules\Micro\Thunderkick
 *
 * Usage: php vendor/bin/codecept run api Phive/Modules/Micro/Thunderkick/BetCest:validBet
 */
class BetCest extends BaseBetCest
{
    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataValidBet
     */
    public function validBet(ApiTester $I, Example $example)
    {
        parent::validBet($I, $example);
    }

    /**
     * @param ApiTester $I
     * @param Example $example
     *
     * @dataProvider dataInvalidBet
     */
    public function invalidBet(ApiTester $I, Example $example)
    {
        parent::invalidBet($I, $example);
    }
}