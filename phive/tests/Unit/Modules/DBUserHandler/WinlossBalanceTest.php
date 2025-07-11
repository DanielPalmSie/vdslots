<?php

namespace Tests\Unit\Modules\DBUserHandler;

use DBUserHandler\Session\WinlossBalance;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../../phive/phive.php';

class WinlossBalanceTest extends TestCase
{
    private \DBUser $user;

    public function setUp(): void
    {
        $user_data = phive('SQL')->loadArray("SELECT id FROM users WHERE email LIKE '%test%' ORDER BY id DESC LIMIT 1");
        $this->user = cu($user_data[0]['id']);
        $this->user->mDel('winloss');
    }

    protected function tearDown(): void
    {
        $this->user->mDel('winloss');
    }

    /**
     * @dataProvider winlossCommonDataProvider
     */
    public function testGetBalanceCommon($data, $assert_win, $assert_loss, $assert_total)
    {
        foreach ($data as $item) {
            // initiate getting balance every time to test reading data from cache store properly
            $win_loss = $this->user->winLossBalance();
            $win_loss->refresh($item['user_game_session_id'], $item['type'], $item['amount']);
        }

        $this->assertEquals($assert_win, $win_loss->getWin());
        $this->assertEquals($assert_loss, $win_loss->getLoss());
        $this->assertEquals($assert_total, $win_loss->getTotal());
    }

    /**
     * @dataProvider winlossSingleDataProvider
     */
    public function testGetBalanceByGame($data, $assert_win, $assert_loss, $assert_total, $user_game_session_id)
    {
        foreach ($data as $item) {
            $win_loss = $this->user->winLossBalance($user_game_session_id);
            $win_loss->refresh($item['user_game_session_id'], $item['type'], $item['amount']);
        }

        $this->assertEquals($assert_win, $win_loss->getWin());
        $this->assertEquals($assert_loss, $win_loss->getLoss());
        $this->assertEquals($assert_total, $win_loss->getTotal());
    }

    /**
     * @return array[]
     */
    public function winlossSingleDataProvider(): array
    {
        return [
            'negative balance' => [
                [
                    [
                        'user_game_session_id' => 1,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 15,
                    ],
                    [
                        'user_game_session_id' => 1,
                        'type' => WinlossBalance::TYPE_WIN,
                        'amount' => 8,
                    ],
                    [
                        'user_game_session_id' => 3,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 10,
                    ],
                ],
                'assert_win' => 8,
                'assert_loss' => 15,
                'assert_total' => -7,
                'user_game_session_id' => 1,
            ],
            'positive balance' => [
                [
                    [
                        'user_game_session_id' => 1234,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 15,
                    ],
                    [
                        'user_game_session_id' => 1234,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 3,
                    ],
                    [
                        'user_game_session_id' => 346,
                        'type' => WinlossBalance::TYPE_WIN,
                        'amount' => 2,
                    ],
                ],
                'assert_win' => 2,
                'assert_loss' => 0,
                'assert_total' => 2,
                'user_game_session_id' => 346,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function winlossCommonDataProvider(): array
    {
        return [
            'negative balance' => [
                [
                    [
                        'user_game_session_id' => 1,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 15,
                    ],
                    [
                        'user_game_session_id' => 1,
                        'type' => WinlossBalance::TYPE_WIN,
                        'amount' => 3,
                    ],
                    [
                        'user_game_session_id' => 2,
                        'type' => WinlossBalance::TYPE_WIN,
                        'amount' => 8,
                    ],
                    [
                        'user_game_session_id' => 2,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 5,
                    ],
                    [
                        'user_game_session_id' => 3,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 10,
                    ],
                ],
                'assert_win' => 11,
                'assert_loss' => 30,
                'assert_total' => -19,
            ],
            'positive balance' => [
                [
                    [
                        'user_game_session_id' => 1234,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 15,
                    ],
                    [
                        'user_game_session_id' => 1234,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 3,
                    ],
                    [
                        'user_game_session_id' => 258,
                        'type' => WinlossBalance::TYPE_WIN,
                        'amount' => 25,
                    ],
                    [
                        'user_game_session_id' => 258,
                        'type' => WinlossBalance::TYPE_LOSS,
                        'amount' => 5,
                    ],
                    [
                        'user_game_session_id' => 346,
                        'type' => WinlossBalance::TYPE_WIN,
                        'amount' => 2,
                    ],
                ],
                'assert_win' => 27,
                'assert_loss' => 23,
                'assert_total' => 4,
            ],
        ];
    }
}
