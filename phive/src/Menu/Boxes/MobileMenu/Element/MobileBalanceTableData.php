<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class MobileBalanceTableData
{
    /**
     * @var int
     */
    private int $amount;

    /**
     * @var string
     */
    private string $alias;

    /**
     * @var string
     */
    private string $id;

    /**
     * @var string
     */
    private string $currency;

    /**
     * @param int $amount
     * @param string $alias
     * @param string $id
     * @param string $currency
     */
    public function __construct(int $amount, string $alias, string $id, string $currency)
    {
        $this->amount = $amount;
        $this->alias = $alias;
        $this->id = $id;
        $this->currency = $currency;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }
}
