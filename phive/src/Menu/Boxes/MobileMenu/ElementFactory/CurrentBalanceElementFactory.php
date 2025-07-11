<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\CurrentBalanceData;

final class CurrentBalanceElementFactory
{
    /**
     * @param string $currency
     * @param string|null $balance
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\CurrentBalanceData
     */
    public function create(string $currency, ?string $balance): CurrentBalanceData
    {
        return new CurrentBalanceData(
            'casino.balance.upc',
            $currency,
            $balance
        );
    }
}
