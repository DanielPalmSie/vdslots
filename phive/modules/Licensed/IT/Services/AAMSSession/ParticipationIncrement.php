<?php


namespace IT\Services\AAMSSession;


class ParticipationIncrement
{
    const TABLE = 'ext_game_participations_increments';

    /**
     * ParticipationIncrement constructor.
     * @param $participation
     * @param $real_increment
     * @param $bonus_increment
     * @param $play_bonus_stake
     */
    public function __construct($participation, $real_increment, $bonus_increment, $play_bonus_stake)
    {
        $stake = $real_increment + $bonus_increment + $play_bonus_stake;
        $insert = [
            'user_id' => $participation['user_id'],
            'participation_id' => $participation['id'],
            'increment' => $stake,
            'stake' => $stake + $participation['stake'],
            'balance' => $real_increment + $participation['balance'],
            'stake_balance_real_bonus' => $bonus_increment,
            'stake_balance_play_bonus' => $play_bonus_stake
        ];

        return phive('SQL')->sh($participation)->insertArray(self::TABLE, $insert);
    }

    public static function countByParticipation($participation) {
        return phive('SQL')->sh($participation)->getValue('', 'COUNT(*)', self::TABLE, ['participation_id' => $participation['id']]);
    }

    public static function getTotalStakedBalance($participation) {
        return phive('SQL')->sh($participation)->getValue('', 'SUM(stake)', self::TABLE, ['participation_id' => $participation['id']]);
    }
}