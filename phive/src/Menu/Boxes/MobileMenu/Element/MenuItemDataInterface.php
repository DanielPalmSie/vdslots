<?php

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

interface MenuItemDataInterface
{
    /**
     * @return string
     */
    public function getIcon(): string;

    /**
     * @return ContentData
     */
    public function getContent(): ContentData;

    /**
     * @return bool
     */
    public function isCurrent(): bool;

    /**
     * @return string
     */
    public function getHref(): string;
}
