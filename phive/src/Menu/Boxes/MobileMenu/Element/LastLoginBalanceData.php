<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class LastLoginBalanceData
{
    /**
     * @var string
     */
    private string $header;

    /**
     * @var string
     */
    private string $currentBalanceAlias;

    /**
     * @var string|null
     */
    private ?string $currentBalance;

    /**
     * @var string
     */
    private string $lastLoginBalanceAlias;

    /**
     * @var string|null
     */
    private ?string $lastLoginBalance;

    /**
     * @var string
     */
    private string $currency;

    /**
     * @param string $header
     * @param string $currentBalanceAlias
     * @param string|null $currentBalance
     * @param string $lastLoginBalanceAlias
     * @param string|null $lastLoginBalance
     * @param string $currency
     */
    public function __construct(
        string $header,
        string $currentBalanceAlias,
        ?string $currentBalance,
        string $lastLoginBalanceAlias,
        ?string $lastLoginBalance,
        string $currency
    ) {
        $this->header = $header;
        $this->currentBalanceAlias = $currentBalanceAlias;
        $this->currentBalance = $currentBalance;
        $this->lastLoginBalanceAlias = $lastLoginBalanceAlias;
        $this->lastLoginBalance = $lastLoginBalance;
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * @return string
     */
    public function getCurrentBalanceAlias(): string
    {
        return $this->currentBalanceAlias;
    }

    /**
     * @return string|null
     */
    public function getCurrentBalance(): ?string
    {
        return $this->currentBalance;
    }

    /**
     * @return string
     */
    public function getLastLoginBalanceAlias(): string
    {
        return $this->lastLoginBalanceAlias;
    }

    /**
     * @return string|null
     */
    public function getLastLoginBalance(): ?string
    {
        return $this->lastLoginBalance;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
}
