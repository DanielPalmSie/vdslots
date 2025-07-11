<?php

declare(strict_types=1);

namespace Videoslots\User\TrophyAward;

final class TrophyAwardService implements TrophyAwardServiceInterface
{
    /**
     * @param int $awardId
     * @param \DBUser $user
     * @param bool $translate
     *
     * @return array
     */
    public function activateTrophyAward(
        int $awardId,
        \DBUser $user,
        bool $translate = true,
        ?bool $returnMobileLaunchUrl = false
    ): array
    {
		dclickStart('use-trophy-award');
        $result = phive('Trophy')->useAward($awardId, $user->getId(), [], 5000000, $translate, $returnMobileLaunchUrl);
        
        if (is_string($result)) {
            $result = ['error' => $result];
        } else {
            if ($result['type'] === 'freespin-bonus') {
                // store free spin in redis to prevent popups on the next game play
                phMsetShard('activated-free-spins', $result['bonus_id'], $user->getId(), 3600);
            }
            $bonus = phive('Bonuses')->getBonus($result['bonus_id']);
            $game = phive('MicroGames')->getByGameId($bonus['game_id']);
            $game = phive('MicroGames')->getCurrentGame($game);

            $result['mobile_game_ref'] = $game['ext_game_name'];
        }
        dclickEnd('use-trophy-award', '');
        phive("Cashier/Aml")->checkBonusToWagerRatio($user);

        return $result;
    }
}
