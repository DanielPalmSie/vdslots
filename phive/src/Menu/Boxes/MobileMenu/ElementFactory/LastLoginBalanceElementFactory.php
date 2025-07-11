<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\ElementFactory;

use Videoslots\Menu\Boxes\MobileMenu\Element\LastLoginBalanceData;

final class LastLoginBalanceElementFactory
{
    /**
     * @param string $currency
     * @param string|null $balance
     *
     * @return \Videoslots\Menu\Boxes\MobileMenu\Element\LastLoginBalanceData|null
     */
    public function create(string $currency, ?string $balance): ?LastLoginBalanceData
    {
        $lastLoginBalance = $this->getLastLoginBalance();
        if (is_null($lastLoginBalance)) {
            return null;
        }

        return new LastLoginBalanceData(
            'casino.last.login.balance.upc',
            'casino.last.login.current.balance.upc',
            $balance,
            'casino.last.login.last.balance.upc',
            $lastLoginBalance,
            $currency
        );
    }

    /**
     * `last_login_balance` field can hold value of type string(containing integer) or contain `false`
     * (if user setting `last-login-balance` is missing)
     * this method "transforms" this value to `?string` - so we could use proper typing,
     * cause now we can't have type `false|string
     * `
     *
     * @return string|null
     */
    private function getLastLoginBalance(): ?string
    {
        $lastLoginBalance = lic('lastLoginBalance', [], cu());

        return filter_var($lastLoginBalance, FILTER_VALIDATE_INT) !== false
            ? $lastLoginBalance
            : null;
    }
}
