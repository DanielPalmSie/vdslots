<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\DataTransferObject;

final class MaintenanceData
{
    /**
     * @var string
     */
    private string $alias;

    /**
     * @var bool
     */
    private bool $isEnabled;

    /**
     * @var string
     */
    private string $from;

    /**
     * @var string
     */
    private string $to;

    /**
     * @param string $alias
     * @param bool $isEnabled
     * @param string $from
     * @param string $to
     */
    public function __construct(
        string $alias,
        bool $isEnabled,
        string $from,
        string $to
    ) {
        $this->alias = $alias;
        $this->isEnabled = $isEnabled;
        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return string
     */
    public function getTo(): string
    {
        return $this->to;
    }
}
