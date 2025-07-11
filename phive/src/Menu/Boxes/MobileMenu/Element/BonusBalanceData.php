<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class BonusBalanceData
{
    /**
     * @var string
     */
    private string $bonusBalanceAlias;

    /**
     * @var string
     */
    private string $currency;

    /**
     * @var string
     */
    private string $bonusBalance;

    /**
     * @param string $bonusBalanceAlias
     * @param string $currency
     * @param string $bonusBalance
     */
    public function __construct(
        string $bonusBalanceAlias,
        string $currency,
        string $bonusBalance
    ) {
        $this->bonusBalanceAlias = $bonusBalanceAlias;
        $this->currency = $currency;
        $this->bonusBalance = $bonusBalance;
    }

    /**
     * @return string
     */
    public function getBonusBalanceAlias(): string
    {
        return $this->bonusBalanceAlias;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getBonusBalance(): string
    {
        return $this->bonusBalance;
    }
}
