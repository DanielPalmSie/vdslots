<?php

declare(strict_types=1);

namespace Videoslots\User\LoginFields\DataTransferObject\LoginDefault;

final class ForgotPasswordData
{
    /**
     * @var string
     */
    private string $type;

    /**
     * @var string
     */
    private string $url;

    /**
     * @var string
     */
    private string $alias;

    /**
     * @param string $type
     * @param string $url
     * @param string $alias
     */
    public function __construct(
        string $type,
        string $url,
        string $alias
    ) {
        $this->type = $type;
        $this->url = $url;
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
}
