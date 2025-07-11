<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\BonusBalanceData;

final class BonusBalanceElementFactory
{
    /**
     * @param string $currency
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\BonusBalanceData|null
     */
    public function create(string $currency): ?BonusBalanceData
    {
        $bonusBalance = $this->getBonus();
        if ($bonusBalance == 0) {
            return null;
        }

        return new BonusBalanceData(
            'casino.bonus.balance.upc',
            $currency,
            (string) ($bonusBalance / 100)
        );
    }

    /**
     * @return int
     */
    private function getBonus(): int
    {
        $user = cu();
        if (is_null($user) || $user === false) {
            return 0;
        }

        $userId = $user->getId();

        $bonusBalance = phive("Bonuses")->getTotalBalanceByUser($userId);

        return ! empty($bonusBalance) ? (int) $bonusBalance : 0;
    }
}
