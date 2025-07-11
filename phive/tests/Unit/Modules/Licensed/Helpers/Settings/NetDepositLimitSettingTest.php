<?php

namespace Tests\Unit\Modules\Licensed\Helpers\Settings;

use DateInterval;
use DateTime;
use DBUser;
use Tests\Unit\TestPhiveBase;

class NetDepositLimitSettingTest extends TestPhiveBase
{
    private DBUser $test_player;

    public function tearDown(): void
    {
        $this->testPhive->cleanupTestPlayer($this->test_player->getId());
    }

    /**
     * @dataProvider monthLimitAgeMatchDataProvider
     * @param string $country
     * @param int $age
     * @param int $assert_limit
     * @param int|null $default_limit
     * @return void
     * @throws \Exception
     */
    public function testGetMonthLimitAgeMatch(string $country, int $age, int $assert_limit, ?int $default_limit)
    {
        $this->test_player = $this->testPhive->getTestPlayer($country);
        $interval = DateInterval::createFromDateString("{$age} years");
        $dob = (new DateTime('now'))->sub($interval)->format('Y-m-d');
        $this->test_player->updateData(['dob' => $dob]);
        $this->test_player->getAttribute('dob', true);
        $limit = lic('getNetDepositMonthLimit', [$this->test_player, $default_limit], $this->test_player);
        $this->assertEquals($assert_limit, $limit);
    }

    /**
     * @return array[]
     */
    public static function monthLimitAgeMatchDataProvider(): array
    {
        return [
            'with-be-bettor-value-and-age-match-gb' => [
                'country' => 'GB',
                'age' => 18,
                'assert_limit' => 12500,
                'default_limit' => 25000,
            ],
            'default-value-and-age-match-gb' => [
                'country' => 'GB',
                'age' => 18,
                'assert_limit' => 50000,
                'default_limit' => null,
            ],
            'with-be-bettor-value-and-age-doesnt-match-gb' => [
                'country' => 'GB',
                'age' => 24,
                'assert_limit' => 25000,
                'default_limit' => 25000,
            ],
            'default-value-and-age-doesnt-match-gb' => [
                'country' => 'GB',
                'age' => 24,
                'assert_limit' => 100000, //LICENSED.IT.NET_DEPOSIT_LIMIT
                'default_limit' => 100000,
            ],
            'default-value-and-age-match-dk' => [
                'country' => 'DK',
                'age' => 18,
                'assert_limit' => 1000000, //LICENSED.DK.NET_DEPOSIT_LIMIT_MODIFIER_BY_AGE
                'default_limit' => null,
            ],
            'default-value-and-age-doesnt-match-dk' => [
                'country' => 'DK',
                'age' => 24,
                'assert_limit' => 15000000, //LICENSED.DK.NET_DEPOSIT_LIMIT
                'default_limit' => null,
            ],
            'default-value-and-age-doesnt-match-it' => [
                'country' => 'IT',
                'age' => 24,
                'assert_limit' => 2000000, //LICENSED.IT.NET_DEPOSIT_LIMIT
                'default_limit' => null,
            ],
            'default-value-and-age-doesnt-match-es' => [
                'country' => 'ES',
                'age' => 24,
                'assert_limit' => 2000000, //LICENSED.ES.NET_DEPOSIT_LIMIT
                'default_limit' => null,
            ],
            'default-value-and-age-match-se' => [
                'country' => 'SE',
                'age' => 19,
                'assert_limit' => 1500000, //LICENSED.SE.NET_DEPOSIT_LIMIT_MODIFIER_BY_AGE
                'default_limit' => null,
            ],
            'default-value-and-age-doesnt-match-se' => [
                'country' => 'SE',
                'age' => 17,
                'assert_limit' => 50000000, //LICENSED.SE.NET_DEPOSIT_LIMIT
                'default_limit' => null,
            ],
        ];
    }
}
