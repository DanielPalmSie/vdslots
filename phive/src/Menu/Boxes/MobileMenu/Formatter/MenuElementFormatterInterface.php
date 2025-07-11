<?php

namespace Videoslots\Menu\Boxes\MobileMenu\Formatter;

interface MenuElementFormatterInterface
{
    /**
     * @return void
     */
    public function toHtml(): void;

    /**
     * @return array
     */
    public function toJson(): array;
}
