<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class CurrentBalanceData
{
    /**
     * @var string
     */
    private string $currentBalanceAlias;

    /**
     * @var string
     */
    private string $currency;

    /**
     * @var string
     */
    private string $balance;

    /**
     * @param string $currentBalanceAlias
     * @param string $currency
     * @param string|null $balance
     */
    public function __construct(
        string $currentBalanceAlias,
        string $currency,
        ?string $balance
    ) {
        $this->currentBalanceAlias = $currentBalanceAlias;
        $this->currency = $currency;
        $this->balance = $balance;
    }

    /**
     * @return string
     */
    public function getCurrentBalanceAlias(): string
    {
        return $this->currentBalanceAlias;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return string|null
     */
    public function getBalance(): ?string
    {
        return $this->balance;
    }
}
