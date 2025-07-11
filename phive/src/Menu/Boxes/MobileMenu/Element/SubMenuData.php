<?php

declare(strict_types=1);

namespace Videoslots\Menu\Boxes\MobileMenu\Element;

final class SubMenuData
{
    /**
     * @var array<\Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuItemData>
     */
    private array $items;

    /**
     * @param array<\Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuItemData> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return array<\Videoslots\Menu\Boxes\MobileMenu\Element\SubMenuItemData>
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
