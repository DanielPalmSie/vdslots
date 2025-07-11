<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class TopButtonData
{
    /**
     * @var string
     */
    private string $alias;

    /**
     * @var string
     */
    private string $url;

    /**
     * @param string $alias
     * @param string $url
     */
    public function __construct(string $alias, string $url)
    {
        $this->alias = $alias;
        $this->url = $url;
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
    public function getUrl(): string
    {
        return $this->url;
    }
}
